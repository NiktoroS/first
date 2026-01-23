<?php

/**
 * @category
 * @author <sirotkin-aa@nko-rr.ru>
 * @since  2022-01-20
 *
 */

namespace app\inc\Storage;

require_once(CNF_DIR . "db.php");
require_once(INC_DIR . "Logs.php");

use app\inc\Logs;
use app\inc\TelegramClass;

class PgSqlStorage
{

    protected $dsn, $logs, $connection, $timestamp, $transactions, $cache = [], $numErrors = 0, $useCache = false;

    /**
     * @param  array $dsnMySql
     */
    public function __construct($dsnPgSql = [], $type = "prod", $host = 0)
    {
        global $logs;
        if (empty($logs)) {
            $this->logs = new Logs("pgSql");
        } else {
            $this->logs = &$logs;
        }
        if (!$dsnPgSql) {
            global $dsnPgSql;
        }
        $this->dsn = $dsnPgSql[$type];
        $this->dsn["host"] = $dsnPgSql[$type]["hosts"][$host];
        if (isset($_SERVER["REQUEST_URI"])) {
            $this->useCache = true;
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     *
     * @param \PgSql\Result $result
     * @return number
     */
    public function affectedRows($result)
    {
        return pg_affected_rows($result);
    }

    /**
     *
     * @return boolean
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
        if (true != $this->checkQueryResult($this->query("BEGIN"), "BEGIN")) {
            throw new \Exception("begin transaction - fail");
        }
        $this->transactions ++;
        return true;
    }

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
     *
     * @return boolean
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
            throw new \Exception("commit transaction - fail");
        }
        $this->transactions --;
        return true;
    }

    public function disconnect()
    {
        $this->close();
    }

    /**
     *
     * @param array|string $val
     * @return array|string
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
     * @param  string $data
     * @return string
     */
    public function escapeString($string)
    {
        if (empty($this->connection)) {
            $this->open();
        }
        return pg_escape_string($this->connection, $string);
    }

    /**
     * @param  string $query
     * @return number
     */
    public function execute($query)
    {
        return $this->affectedRows($this->query($query));
    }

    /**
     * @param  timestamp default null
     * @return number|boolean
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
     * @param  string $table
     * @param array $row
     * @param array $idsKey
     * @return number
     */
    public function insertOrUpdateRow($table, $row = [], $idsKey = ["id"])
    {
        if (!$row) {
            return 0;
        }
        return $this->insertOrUpdateRows($table, [$row], $idsKey);
    }

    /**
     * @param string $table
     * @param array $rows
     * @param array $idsKey
     * @return number
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
     * @param string $table
     * @param array  $row
     * @param string $id
     * @return string
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
     * @return number
     */
    public function insertRows($table, $rows = [])
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
     *
     * @param \PgSql\Result $result
     * @return string|number|boolean
     */
    public function lastInsertID($result)
    {
        return pg_last_oid($result);
    }

    public function open()
    {
        global $pgDbAll;

        $host = $this->dsn["host"];
        $name = $this->dsn["name"];
        if (empty($pgDbAll[$host][$name]["connection"])) {
            do {
                $connString = "host={$host} port={$this->dsn["port"]} dbname={$name} user={$this->dsn["user"]} password=" . addslashes($this->dsn["pass"]);
                $pgDbAll[$host][$name]["connection"] = pg_connect($connString);
                if (!$pgDbAll[$host][$name]["connection"]) {
                    $this->logs->add("Error connect connString: {$connString}");
                    $this->numErrors ++;
                    $exception = new \Exception("ConnectError: {$host} {$this->dsn["user"]} {$this->dsn["pass"]} {$pgDbAll[$host][$name]["connection"]}");
                    $log       = new Logs("PgSqlConnectError");
                    $log->add($exception);
                    if (5 == $this->numErrors) {
                        throw $exception;
                    }
                    sleep(38);
                }
            } while (!$pgDbAll[$host][$name]["connection"]);
            $pgDbAll[$host][$name]["transactions"] = 0;
        }
        $this->connection   = &$pgDbAll[$host][$name]["connection"];
        $this->transactions = &$pgDbAll[$host][$name]["transactions"];
        $this->timestamp    = time();
    }

    /**
     *
     * @param string $query
     * @return \PgSql\Result|boolean
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
     * @return array
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
     * @param  array  $data
     * @param  bool   $escape
     * @return \PgSql\Result|boolean
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
     * @param  string $sql
     * @return int
     */
    public function queryInt($query)
    {
        return intval(round($this->queryOne($query)));
    }

    /**
     * @param  string $table
     * @param  array  $conditions
     * @param  string $orderBy
     * @return array
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
     * @param  string $sql
     * @return string
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
     * @param  string $query
     * @return void|array|boolean
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
            return;
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
     * @param  string $table
     * @param  string $id
     * @return array
     */
    public function queryRowById($table, $id)
    {
        return $this->queryRow("SELECT * FROM \"{$table}\" WHERE id = " . intval($id));
    }

    /**
     * @param  string $query
     * @param  bool   $firstFieldIsKey
     * @return array
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
     */
    public function queryRowsK($query)
    {
        return $this->queryRows($query, true);
    }

    /**
     *
     * @return boolean
     */
    public function rollback()
    {
        if (true != $this->checkQueryResult($this->query("ROLLBACK"), "ROLLBACK")) {
            throw new \Exception("rollback - fail");
        }
        $this->transactions = 0;
        return true;
    }

    /**
     *
     * @param string $table
     * @param array $conditions
     * @param string $orderBy
     * @param string $addTable
     * @param string $fields
     * @return void|array|boolean
     */
    public function selectRow($table, $conditions = [], $orderBy = "", $addTable = "", $fields = "*")
    {
        if (is_array($table)) {
            extract($table);
        }
        $table = $this->escapeTable($table);
        $query = "SELECT {$fields} FROM {$table}";
        if (!empty($addTable)) {
            $query .= $addTable;
        }
        if ($conditions) {
            $conditionsNew = [];
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
     * @param string $table
     * @param array $conditions
     * @param string $orderBy
     * @param number $offset
     * @param number $limit
     * @param string $addTable
     * @param boolean $distinct
     * @return array
     */
    public function selectRows($table, $conditions = [], $orderBy = "name", $offset = 0, $limit = 0, $addTable = "", $distinct = false)
    {
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
            $conditionsNew = [];
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
     * @param boolean $useCache
     */
    public function setUseCache($useCache = true)
    {
        $this->useCache = $useCache;
    }

    /**
     *
     * @param string $table
     * @param array $row
     * @param array $keys
     * @return boolean
     */
    public function updateRow($table = "", $row = [], $keys = ["id"])
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        if (isset($row["mod_date"])) {
            unset($row["mod_date"]);
        }
        $this->correctData($table, $row);
        $setArr   = [];
        $whereArr = [];
        foreach ($row as $key => $val) {
            $str = "\"{$key}\" = {$val}";
            if (in_array($key, $keys)) {
                $whereArr[] = $str;
            } else {
                $setArr[] = $str;
            }
        }
        $this->query("UPDATE \"{$table}\" SET " . join(", ", $setArr) . " WHERE " . join(" AND ", $whereArr));
        return true;
    }

    /**
     *
     * @param boolean $result
     * @param string $query
     * @return boolean
     */
    protected function checkQueryResult($result = true, $query = "")
    {
        if (false === $result or pg_last_error($this->connection)) {
            $error = pg_last_error($this->connection);
            $request   = empty($_SERVER["REQUEST_URI"]) ? "" : "request: " . $_SERVER["REQUEST_URI"] . ($_POST ? "?" . http_build_query($_POST) : "") . "\n\n";
            $exception = new \Exception("{$request}{$query} [{$error}]");
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
     * @param string $template
     * @param array  $data
     * @param boolean $escape
     * @return string|array|string
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
     *
     * @param \Exception $exception
     */
    private function addLog(\Exception $exception)
    {
        $errorlog = new Logs("PgSqlError");
        $errorlog->add($exception);
        $errorlog->add(var_export($this->connection, true));
        $this->logs->add($exception);

        if (empty($_SERVER["REQUEST_URI"])) {
            fwrite(STDERR, strval($exception) . "\n");
        }
    }

    /**
     * приведение данных
     * @param string $table
     * @param array  &$data
     * @param array $columns
     */
    private function correctData($table, &$data = [], $columns = [])
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
        if (isset($columns["created_at"]) and empty($data["created_at"])) {
            $data["created_at"] = "NOW()";
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
     *
     * @param \Exception $exception
     */
    private function sendSms(\Exception $exception)
    {
        if (!is_file(INC_DIR . "TelegramClass.php")) {
            return;
        }
        require_once(INC_DIR . "TelegramClass.php");
        $telegram = new TelegramClass();
        $telegram->sendMessage($exception, "PgSql");
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

}
