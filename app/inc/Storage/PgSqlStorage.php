<?php

/**
 * @category
 * @author <sirotkin-aa@nko-rr.ru>
 * @since  2022-01-20
 *
 */

namespace app\inc\Storage;

require_once(CNF_DIR . "db.php");

use app\inc\Logs;

class PgSqlStorage
{

    protected $dsn, $log, $connection, $timestamp, $transactions, $cache = [], $numErrors = 0, $useCache = false;

    /**
     * @param  array $dsnMySql
     */
    public function __construct($dsnPgSql = [], $type = "prod", $host = 0)
    {
        global $log;
        if (empty($log)) {
            $this->log = new Logs();
        } else {
            $this->log = &$log;
        }
        if (!$dsnPgSql) {
            global $dsnPgSql;
        }
        $this->dsn = $dsnPgSql[$type];
        $this->dsn["host"] = $dsnPgSql[$type]["hostRows"][$host];
        if (isset($_SERVER["REQUEST_URI"])) {
            $this->useCache = true;
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @throws
     */
    public function open()
    {
        global $pgDbAll;

        $host = $this->dsn["host"];
        $name = $this->dsn["name"];
        if (empty($pgDbAll[$host][$name]["conn"])) {
            do {
                $connString = "host={$this->dsn["host"]} port={$this->dsn["port"]} dbname={$this->dsn["dbname"]} user={$this->dsn["user"]} password={$this->dsn["password"]}";
                $pgDbAll[$host][$name]["connection"] = pg_connect($connString);
                if (!$pgDbAll[$host][$name]["connection"]) {
                    $this->log->add("Error connect connString: {$connString}");
                    $this->numErrors ++;
                    $exception = new \Exception("ConnectError: {$host} {$this->dsn["user"]} {$this->dsn["password"]} {$pgDbAll[$host][$name]["connection"]}");
                    $log       = new Logs("PgSqlConnectError");
                    $log->add($exception);
                    if (5 == $this->numErrors) {
                        throw $exception;
                    }
                    sleep(38);
                }
            } while (!$pgDbAll[$host][$name]["connection"]);
            $pgDbAll[$host][$name]["transactions"] = 0;
/*
            $pgDbAll[$host][$name]["link"]->autocommit(true);
            $pgDbAll[$host][$name]["link"]->query("SET NAMES utf8");
            $pgDbAll[$host][$name]["link"]->query("SET sql_mode = 'ALLOW_INVALID_DATES'");
            $pgDbAll[$host][$name]["link"]->query("SET wait_timeout = 288000");
**/
        }
        $this->connection   = &$pgDbAll[$host][$name]["connection"];
        $this->transactions = &$pgDbAll[$host][$name]["transactions"];
        $this->timestamp    = time();
    }

    /**
     * @throws
     */
    public function close()
    {
        global $pgDbAll;
        if (empty($this->connection)) {
            return;
        }
        while ($this->transactions) {
            $this->commit();
        }
        pg_close($this->connection);
        unset($this->connection, $pgDbAll[$this->dsn["host"]][$this->dsn["name"]]["connection"]);
    }

    /**
     * @param  string $query
     *
     * @return true
     * @throws
     */
    public function query($query)
    {
        $this->cache = [];
        do {
            if (empty($this->connection)) {
                $this->open();
            }
            $result = pg_query($this->connection, $query);
        } while (false === $this->checkQueryResult($result, $query));
        return $result;
    }

    /**
     * @param  string $query
     * @param  array  $data
     * @param  bool   $escape
     *
     * @return mixed
     * @throws
     */
    public function queryForSelect($query, $data = [], $escape = true)
    {
        if ($data) {
            $query = $this->makeString($query, $data, $escape);
        }
        do {
            if (empty($this->connection)) {
                $this->open();
            }
            $t  = microtime(true);
            $result = pg_query($this->connection, $query);
            $dt = microtime(true) - $t;
            if ($dt > 5.0) {
                $logSqlLong = new Logs("pgSqlQueryLong");
                $logSqlLong->add($dt . " | " . $query);
            }
        } while (false === $this->checkQueryResult($result, $query));
        return $result;
    }

    /**
     * @param  string $query
     * @return array
     * @throws
     */
    public function queryRow($query)
    {
        $row = $this->getCache($query, "row");
        if (false !== $row) {
            return $row;
        }
        $result = $this->queryForSelect($query);
        $numRows = pg_num_rows($result);
        if (!$numRows) {
            return [];
        }
        if ($numRows > 1) {
            $log = new Logs("PgSqlWarning");
            $log->add("more one rows in: {$query}");
        }
        $row = pg_fetch_assoc($result);
        $this->setCache($query, "row", $row);
        return $row;
    }

    /**
     * @param  string $sql
     * @return string
     * @throws
     */
    public function queryOne($query)
    {
        $row  = $this->queryRow($query);
        if (!$row) {
            return "";
        }
        $keys = array_keys($row);
        return $row[$keys[0]];
    }

    /**
     * @param  string $sql
     * @return int
     * @throws
     */
    public function queryInt($query)
    {
        return intval(round($this->queryOne($query)));
    }

    /**
     * @param  string $query
     * @param  bool   $firstFieldIsKey
     * @return array
     * @throws
     */
    public function queryRows($query, $firstFieldIsKey = false)
    {
        $rows = $this->getCache($query, "rows");
        if (false !== $rows) {
            return $rows;
        }
        $rows   = [];
        $result = $this->queryForSelect($query);
        if (!$result) {
            return $rows;
        }
        $numRows = pg_num_rows($result);
        if (!$numRows) {
            return $rows;
        }
        while ($row = pg_fetch_assoc($result)) {
            if (true == $firstFieldIsKey) {
                if (1 == count($row)) {
                    $row = array_values($row);
                    $row = $key = $row[0];
                } else {
                    $key = array_shift($row);
                }
                $rows[$key] = $row;
            } else {
                $rows[] = $row;
            }
        }
        $this->setCache($query, "rows", $rows);
        return $rows;
    }

    /**
     * @param  string $query
     * @return array
     * @throws
     */
    public function queryCol($query)
    {
        $rows = $this->getCache($query, "Col");
        if (false !== $rows) {
            return $rows;
        }
        $rows   = [];
        $result = $this->queryForSelect($query);
        $numRows = pg_num_rows($result);
        if (!$numRows) {
            return $rows;
        }
        while ($row = pg_fetch_row($result)) {
            $rows[] = $row[0];
        }
        $result->close();
        $this->setCache($query, "Col", $rows);
        return $rows;
    }

    /**
     * @param  string $query
     * @return array
     * @throws
     */
    public function queryRowsK($query)
    {
        return $this->queryRows($query, true);
    }

    /**
     * @param  string $table
     * @param  array  $conditions
     * @param  string $orderBy
     * @return array
     * @throws
     */
    public function queryObj($table, $conditions = [], $orderBy = "name")
    {
        $sql = "SELECT id, name FROM \"{$table}\"";
        if ($conditions) {
            $sql .= " WHERE " . join(" AND ", $conditions);
        }
        $sql .= " ORDER BY {$orderBy}";
        return $this->queryRowsK($sql);
    }

    /**
     * @param  string $table
     * @param  string $id
     * @return array
     * @throws
     */
    public function queryRowById($table, $id)
    {
        return $this->queryRow("SELECT * FROM \"{$table}\" WHERE id = " . intval($id));
    }

    /**
     * @param string $table
     * @param array  $row
     * @param string $id
     *
     * @return bool
     * @throws
     */
    public function updateRow($table, $row, $id = "id")
    {
        if (isset($row["mod_date"])) {
            unset($row["mod_date"]);
        }
        $this->correctData($table, $row);
        $setArr   = [];
        $whereArr = [];
        foreach ($row as $key => $val) {
            $str = "\"{$key}\" = {$val}";
            if ($key == $id) {
                $whereArr[] = $str;
            } else {
                $setArr[] = $str;
            }
        }
        $this->query("UPDATE \"{$table}\" SET " . join(", ", $setArr) . " WHERE " . join(" AND ", $whereArr));
        return true;
    }

    /**
     * @param string $table
     * @param array  $row
     * @param string $id
     *
     * @return int lastInsertId
     * @throws
     */
    public function insertRow($table, $row, $id = "id")
    {
        unset($row[$id]);
        if (isset($row["mod_date"])) {
            unset($row["mod_date"]);
        }
        $this->correctData($table, $row);
        $query = "
INSERT INTO \"{$table}\" (\"" . join("\", \"", array_keys($row)) . "\")
VALUES (" . join(", ", $row) . ") RETURNING \"{$id}\"";
        return $this->queryOne($query);
    }

    /**
     * @param string $table
     * @param array  $rows
     *
     * @return int
     * @throws
     */
    public function insertRows($table, $rows)
    {
        $fields = "";
        $query = "
SELECT column_name
  FROM information_schema.columns
 WHERE table_schema = 'public'
   AND table_name = '{$table}'";
        $columns = $this->queryRowsK($query);
        foreach ($rows as $keyRow => $row) {
            $this->correctData($table, $row, $columns);
            if (!$fields) {
                $fields = "(\"" . join("\", \"", array_keys($row)) . "\")";
            }
            $rows[$keyRow] = "(" . join(", ", $row) . ")";
        }
        return $this->affectedRows($this->query("INSERT INTO {$table} {$fields} VALUES " . join(", ", $rows)));
    }

    /**
     * @param string $table
     * @param array  $rows
     *
     * @return int
     * @throws
     */
    public function insertOrUpdateRows($table, $rows = [], $idsKey = ["id"])
    {
        if (!$rows) {
            return 0;
        }
        $fields  = "";
        $columns = $this->queryRowsK("
SELECT column_name
  FROM information_schema.columns
 WHERE table_schema = 'public'
   AND table_name = '{$table}'");
        foreach ($rows as $keyRow => $row) {
            $this->correctData($table, $row, $columns);
            if (!$fields) {
                $keys = $keysUpd = array_map(function($key) { return "\"" . $key . "\""; }, array_keys($row));
                $fields  = " (" . join(", ", $keys) . ") ";
                if ($key = array_search("\"ins_date\"", $keysUpd)) {
                    unset($keysUpd[$key]);
                }
            }
            $rows[$keyRow] = $row;
        }
        if (!$keysUpd) {
            var_dump($table, $rows);
            exit;
        }
        $strRows    = [];
        $fieldStr   = [];
        $updFields  = [];
        if (!is_array($idsKey)) {
            $idsKey = array_map("trim", explode(",", $idsKey));
        }
        foreach ($rows as $key => $row) {
            $strRows[] = join(",", array_map(function($val) { return $val;}, $row));
            if (!$key) {
                $fieldStr = join(",", array_map(function($var) { return "\"{$var}\"";}, array_keys($row)));
                foreach ($idsKey as $idKey) {
                    unset($row[$idKey]);
                }
                $updFields = array_keys($row);
            }
        }
        $query = "
INSERT INTO \"{$table}\" ({$fieldStr})
VALUES (" . join("),(", $strRows) . ")
ON CONFLICT (\"" . join("\",\"", $idsKey) . "\")
DO
   UPDATE SET ";
        if (count($updFields) > 1) {
            $query .=
            "(" . join(",", array_map(function($field) { return "\"{$field}\""; }, $updFields)) . ") = " .
            "(" . join(",", array_map(function($field) { return "EXCLUDED.{$field}"; }, $updFields)) . ")";
        } else {
            $query .= join(",", array_map(function($field) { return "\"{$field}\" = EXCLUDED.{$field}"; }, $updFields));
        }
        return $this->affectedRows($this->query($query));
    }

    /**
     * @param  string $table
     * @param  array  $row
     * @param  array  $idsKey
     *
     * @return int lastInsertId
     * @throws
     */
    public function insertOrUpdateRow($table, $row = [], $idsKey = ["id"])
    {
        if (!$row) {
            return 0;
        }
        return $this->insertOrUpdateRows($table, [$row], $idsKey);
    }

    /**
     * @return int
     */
    public function lastInsertID($result)
    {
        return pg_last_oid($result);
    }

    /**
     * @return int
     */
    public function affectedRows($result)
    {
        return pg_affected_rows($result);
    }

    /**
     * @param  string $query
     * @return int
     * @throws
     */
    public function execute($query)
    {
        return $this->affectedRows($this->query($query));
    }

    /**
     * @return true
     * @throws
     */
    public function begin()
    {
        if (empty($this->transactions)) {
            $this->transactions = 0;
        }
        if ($this->transactions > 0) {
            // транзакция уже открыта
            $this->transactions ++;
            return true;
        }
        if (empty($this->connection)) {
            $this->open();
        }
//        $this->myDb->autocommit(false);
        if (true != $this->checkQueryResult($this->query("BEGIN"), "BEGIN")) {
            throw new Exception("begin transaction - fail");
        }
        $this->transactions ++;
        return true;
    }

    /**
     * @return true
     * @throws
     */
    public function commit()
    {
        if (empty($this->transactions)) {
            // нет открытых транзакций
            $this->transactions = 0;
            return true;
        }
        if ($this->transactions > 1) {
            $this->transactions --;
            return true;
        }
        if (true != $this->checkQueryResult($this->query("COMMIT"), "COMMIT")) {
            $this->rollback();
            throw new Exception("commit transaction - fail");
        }
        $this->transactions --;
        return true;
    }

    /**
     * @return true
     * @throws
     */
    public function rollback()
    {
        if (true != $this->checkQueryResult($this->query("ROLLBACK"), "ROLLBACK")) {
            throw new Exception("rollback - fail");
        }
        $this->transactions = 0;
        return true;
    }

    public function disconnect()
    {
        $this->close();
    }

    /**
     * @param  timestamp default null
     * @return int
     */
    public function getDay($timestamp = null)
    {
        if (!$timestamp) {
            $timestamp = $this->timestamp;
        } else {
            $timestamp = intval($timestamp);
        }
        return mktime(0, 0, 0, date("n", $timestamp), date("j", $timestamp), date("Y", $timestamp));
    }

    /**
     * @param bool $useCache
     */
    public function setUseCache($useCache = true)
    {
        $this->useCache = $useCache;
    }

    /**
     * @param  string $template
     * @param  array  $data
     * @param  bool   $escape
     * @return string
     */
    protected function makeString($template, $row, $escape = true)
    {
        if (!is_array($row)) {
            return "";
        }
        $search  = [];
        $replace = [];
        foreach ($row as $key => $value) {
            $search[]  = "#{$key}#";
            if ($escape) {
                $replace[] = $this->escapeString($value);
            } else {
                $replace[] = $value;
            }
        }
        return str_replace($search, $replace, $template);
    }

    protected function ping()
    {
        if (empty($this->connection)) {
            $this->open();
        }
        pg_ping($this->connection);
    }

    /**
     * @return bool
     * @throws
     */
    protected function checkQueryResult($result = true, $query = "")
    {
        if (false === $result or pg_last_error($this->connection)) {
            $error = pg_last_error($this->connection);
            $request   = empty($_SERVER["REQUEST_URI"]) ? "" : "request: " . $_SERVER["REQUEST_URI"] . ($_POST ? "?" . http_build_query($_POST) : "") . "\n\n";
            $exception = new Exception("{$request}{$query} [{$error}]");
            if ("" == $request and $this->numErrors < 5 and !$this->transactions) {
                // при offline запуске и отсутствии открытой транзакции
                // попытаемся переконнектится и выполнить запрос еще раз
                $this->numErrors ++;
                $this->close();
                sleep(70);
                return false;
            }
            $this->addLog($exception);
            if ($this->transactions and true === $this->rollback()) {
                $this->transactions --;
            }
            $this->sendSms($exception);
            throw $exception;
        }
        $this->numErrors = 0;
        return true;
    }

    /**
     * @param Exception $exception
     */
    private function addLog($exception)
    {
        $errorlog = new Logs("PgSqlError");
        $errorlog->add($exception);
        $errorlog->add(var_export($this->connection, true));
        $this->log->add($exception);

        if (empty($_SERVER["REQUEST_URI"])) {
            fwrite(STDERR, strval($exception) . "\n");
        }
    }

    /**
     * @param Exception $exception
     */
    private function sendSms($exception)
    {
        if (!is_file(INC_DIR . "Telegram.class.php")) {
            return;
        }
        require_once(INC_DIR . "Telegram.class.php");
        $telegram = new Telegram();
        $telegram->sendMessage($exception, "PgSql");
    }

    /**
     * @param  string $query
     * @param  string $type
     * @return array|bool
     */
    private function getCache($query, $type)
    {
        if ($this->useCache) {
            $md5 = md5($query);
            if (isset($this->cache[$type][$md5])) {
                return $this->cache[$type][$md5];
            }
        }
        return false;
    }

    /**
     * @param string $query
     * @param string $type
     * @param array  $data
     */
    private function setCache($query, $type, $data)
    {
        if ($this->useCache) {
            if (!isset($this->cache[$type])) {
                $this->cache[$type] = [];
            }
            $this->cache[$type][md5($query)] = $data;
        }
    }

    /**
     * приведение данных
     * @param string $table
     * @param array  &$row
     * @param array  $columns
     */
    private function correctData($table, &$data, $columns = [])
    {
         if (!$columns) {
            $query = "
SELECT column_name
  FROM information_schema.columns
 WHERE table_schema = 'public'
   AND table_name = '{$table}'";
            $columns = $this->queryRowsK($query);
        }
        $table = $this->escapeTable($table);
        if (isset($columns["ins_date"]) and empty($data["ins_date"])) {
            $data["ins_date"] = "NOW()";
        }
        $colsKeys = array_keys($columns);
        foreach ($data as $key => $value) {
            if (!$key or !in_array($key, $colsKeys)) {
                unset($data[$key]);
                continue;
            }
            if (is_null($value)) {
                $data[$key] = "null";
                continue;
            }
            if (is_bool($value)) {
                $data[$key] = $value ? "true" : "false";
                continue;
            }
            if (in_array($value, ["NULL", "NOW()", "RAND()"])) {
                continue;
            }
            if (is_string($value)) {
                $data[$key] = "'" . $this->escapeString($value) . "'";
            }
        }
    }

    /**
     * string $table
     * array  $conditions
     * string $orderBy
     * int    $offset
     * int    $limit
     * string $addTable
     * bool   $distinct
     * @return array
     * @throws
     */
    public function selectRows($table, $conditions = [], $orderBy = "name", $offset = 0, $limit = 0, $addTable = "", $distinct = false) {
        if (is_array($table)) {
            extract($table);
        }
        $table = $this->escapeTable($table);
        $query = "SELECT ";
        if ($distinct) {
            $query .= "DISTINCT ";
        }
        $query .= "{$table}.* FROM {$table}";
        if (!empty($addTable)) {
            $query .= $addTable;
        }
        if ($conditions) {
            $conditionsNew = array ();
            foreach ($conditions as $key => $condition) {
                if (is_numeric($key)) {
                    $conditionNew = $condition;
                } elseif  (in_array($condition, [false, true, "false", "true"])) {
                    $conditionNew = "\"" . $this->escapeString($key) . "\" = " . $this->escapeString($condition);
                } else {
                    $conditionNew = "\"" . $this->escapeString($key) . "\" = '" . $this->escapeString($condition) . "'";
                }
                $conditionsNew[] = $conditionNew;
            }
            $query .= " WHERE " . join(" AND ", $conditionsNew);
        }
        if ($orderBy) {
            $query .= " ORDER BY {$orderBy}";
        }
        if ($limit) {
            $query .= " LIMIT {$limit}";
            if ($offset) {
                $query .= " OFFSET {$offset}";
            }
        }
        return $this->queryRows($query);
    }

    /**
     *
     * @param string $table
     * @param array $conditions
     * @param string $addTable
     *
     * @return array
     * @throws
     */
    public function selectRow($table, $conditions = [], $addTable = "", $orderBy = "") {
        if (is_array($table)) {
            extract($table);
        }
        $table = $this->escapeTable($table);

        $query = "SELECT {$table}.* FROM {$table}";
        if (!empty($addTable)) {
            $query .= $addTable;
        }
        if ($conditions) {
            $conditionsNew = array ();
            foreach ($conditions as $key => $condition) {
                if (is_numeric($key)) {
                    $conditionNew = $condition;
                } else {
                    $conditionNew = "\"" . $this->escapeString($key) . "\" = '" . $this->escapeString($condition) . "'";
                }
                $conditionsNew[] = $conditionNew;
            }
            $query .= " WHERE " . join(" AND ", $conditionsNew);
        }
        if ($orderBy) {
            $query .= " ORDER BY {$orderBy}";
        }
        $query .= " LIMIT 1";
        return $this->queryRow($query);
    }

    /**
     * @param  string $data
     * @return string
     */
    public function escapeString($data)
    {
        if (empty($this->connection)) {
            $this->open();
        }
        return pg_escape_string($this->connection, $data);
    }

    /**
     * array|string $arr
     * @return array
     */
    public function escape($val)
    {
        if (is_array($val)) {
            foreach ($val as $key=>$row) {
                $val[$key] = $this->escape($row);
            }
            return $val;
        } else {
            if (in_array($val, [null, false, true, "NULL", "NOW()"]) && !is_numeric($val)) {
                return $val;
            } else {
                return "'" . $this->escapeString($val) . "'";
            }
        }
    }

    /**
     *
     * @param string &$tableName
     * @return string
     */
    private function escapeTable(&$tableName)
    {
        $tableName = str_replace("\"", "", $tableName);
        $escapedRows = [];
        foreach (explode(".", $tableName) as $name) {
            $escapedRows[] = "\"" . $this->escapeString($name) . "\"";
        }
        $tableName = implode(".", $escapedRows);
        return $tableName;
    }
}
