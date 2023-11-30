<?php

/**
 * @category
 * @author <first@mail.ru>
 * @since  2018-03-30
 *
 */

#namespace app\inc\Storage;

require_once(CNF_DIR . "db.php");
require_once(INC_DIR . "Logs.class.php");

class MySqlStorage
{

    protected $dsn, $myDb, $timestamp, $transactions, $cache = array (), $numErrors = 0, $useCache = false;

    /**
     * @param  array $dsnMySql
     */
    public function __construct($dsnMySql = array ())
    {
        global $logs;
        if (empty($logs)) {
            $this->logs = new Logs();
        } else {
            $this->logs = &$logs;
        }
        if (!$dsnMySql) {
            global $dsnMySql;
        }
        $this->dsn = $dsnMySql;
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
        global $myDbAll;

        $host = $this->dsn["host"];
        $name = $this->dsn["name"];
        if (empty($myDbAll[$host][$name]["link"])) {
            do {
                $myDbAll[$host][$name]["link"] = new mysqli($host, $this->dsn["user"], $this->dsn["pass"], $name);
                if ($myDbAll[$host][$name]["link"]->connect_error) {
                    $this->numErrors ++;
                    $exception = new Exception("ConnectError: {$host} {$this->dsn["user"]} {$this->dsn["pass"]} {$myDbAll[$host][$name]["link"]->connect_error}", $myDbAll[$host][$name]["link"]->connect_errno);
                    $log       = new Logs("MySqlConnectError");
                    $log->add((string)($exception));
                    if (5 == $this->numErrors) {
                        throw $exception;
                    }
                    sleep(38);
                }
            } while ($myDbAll[$host][$name]["link"]->connect_error);
            $myDbAll[$host][$name]["transactions"] = 0;
            $myDbAll[$host][$name]["link"]->autocommit(true);
            $myDbAll[$host][$name]["link"]->query("SET NAMES utf8");
            $myDbAll[$host][$name]["link"]->query("SET sql_mode = 'ALLOW_INVALID_DATES'");
            $myDbAll[$host][$name]["link"]->query("SET wait_timeout = 288000");
            //            $myDbAll[$host][$name]["link"]->query("SET time_zone='+03:00'");
        }
        $this->myDb         = &$myDbAll[$host][$name]["link"];
        $this->transactions = &$myDbAll[$host][$name]["transactions"];
        $this->timestamp    = time();
    }

    /**
     * @throws
     */
    public function close()
    {
        global $myDbAll;
        if (empty($this->myDb)) {
            return;
        }
        while ($this->transactions) {
            $this->commit();
        }
        $this->myDb->close();
#        unset($this->myDb, $myDbAll[$this->dsn["host"]][$this->dsn["name"]]["link"]);
    }

    /**
     * @param  string $sql
     *
     * @return true
     * @throws
     */
    public function query($sql)
    {
        $this->cache = array ();
        do {
            if (empty($this->myDb)) {
                $this->open();
            }
        } while (false === $this->checkQueryResult(@$this->myDb->real_query($sql), $sql));
        return true;
    }

    /**
     * @param  string $sql
     * @param  array  $data
     * @param  bool   $escape
     *
     * @return mixed
     * @throws
     */
    public function queryForSelect($sql, $data = array (), $escape = true)
    {
        if ($data) {
            $sql = self::makeString($sql, $data, $escape);
        }
        do {
            if (empty($this->myDb)) {
                $this->open();
            }
            $t  = microtime(true);
            $result = @$this->myDb->query($sql);
            $dt = microtime(true) - $t;
            if ($dt > 5.0) {
                $logSqlLong = new Logs("mySqlQueryLong");
                $logSqlLong->add($dt . " | " . $sql);
            }
        } while (false === $this->checkQueryResult($result, $sql));
        return $result;
    }

    /**
     * @param  string $sql
     * @return array
     * @throws
     */
    public function queryRow($sql)
    {
        $row = $this->getCache($sql, "row");
        if (false !== $row) {
            return $row;
        }
        $result = $this->queryForSelect($sql);
        if (!$result->num_rows) {
            @$result->close();
            return array ();
        }
        if ($result->num_rows > 1) {
            $log = new Logs("mySqlWarning");
            $log->add("more one rows in: " . $sql);
        }
        $row = $result->fetch_assoc();
        $result->close();
        $this->setCache($sql, "row", $row);
        return $row;
    }

    /**
     * @param  string $sql
     * @return string
     * @throws
     */
    public function queryOne($sql)
    {
        $row  = $this->queryRow($sql);
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
    public function queryInt($sql)
    {
        return intval($this->queryOne($sql));
    }

    /**
     * @param  string $sql
     * @param  bool   $firstFieldIsKey
     * @return array
     * @throws
     */
    public function queryRows($sql, $firstFieldIsKey = false)
    {
        $rows = $this->getCache($sql, "rows");
        if (false !== $rows) {
            return $rows;
        }
        $rows   = array ();
        $result = $this->queryForSelect($sql);
        if (!$result) {
            return $rows;
        }
        if (!$result->num_rows) {
            @$result->close();
            return $rows;
        }
        while ($row = $result->fetch_assoc()) {
            if (true == $firstFieldIsKey) {
                $key = array_shift($row);
                if (1 == count($row)) {
                    $row = array_values($row);
                    $row = $row[0];
                }
                $rows[$key] = $row;
            } else {
                $rows[] = $row;
            }
        }
        $result->close();
        $this->setCache($sql, "rows", $rows);
        return $rows;
    }

    /**
     * @param  string $sql
     * @return array
     * @throws
     */
    public function queryCol($sql)
    {
        $data = $this->getCache($sql, "Col");
        if (false !== $data) {
            return $data;
        }
        $data   = array ();
        $result = $this->queryForSelect($sql);
        if (!$result->num_rows) {
            @$result->close();
            return $data;
        }
        while ($row = $result->fetch_row()) {
            $data[] = $row[0];
        }
        $result->close();
        $this->setCache($sql, "Col", $data);
        return $data;
    }

    /**
     * @param  string $sql
     * @return array
     * @throws
     */
    public function queryRowsK($sql)
    {
        return $this->queryRows($sql, true);
    }

    /**
     * @param  string $table
     * @param  array  $conditions
     * @param  string $orderBy
     * @return array
     * @throws
     */
    public function queryObj($table, $conditions = array (), $orderBy = "name")
    {
        $sql = "SELECT id, name FROM " . $table;
        if ($conditions) {
            $sql .= " WHERE " . join(" AND ", $conditions);
        }
        $sql .= " ORDER BY " . $orderBy;
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
        return $this->queryRow("SELECT * FROM " . $table . " WHERE id = " . intval($id));
    }

    /**
     * @param string $table
     * @param array  $data
     * @param string $id
     *
     * @return bool
     * @throws
     */
    public function updateRow($table, $row, $id = "id")
    {
        if (isset($row["_updated"])) {
            unset($row["_updated"]);
        }
        $this->correctData($table, $row);
        $setArr   = array ();
        $whereArr = array ();
        foreach ($row as $key => $val) {
            $str = "`" . $key . "` = " . $val;
            if ($key == $id) {
                $whereArr[] = $str;
            } else {
                $setArr[] = $str;
            }
        }
        $this->query("UPDATE " . $table . " SET " . join(", ", $setArr) . " WHERE " . join(" AND ", $whereArr));
        return true;
    }

    /**
     * @param string $table
     * @param array  $data
     * @param string $id
     *
     * @return int lastInsertId
     * @throws
     */
    public function insertRow($table, $row, $id = "id")
    {
        unset($row[$id]);
        if (isset($row["_updated"])) {
            unset($row["_updated"]);
        }
        $this->correctData($table, $row);
        $this->query("
            INSERT INTO " . $table . " (`" . join("`, `", array_keys($row)) . "`)
            VALUES (" . join(", ", $row) . ")
        ");
        return $this->lastInsertID();
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
        $columns = $this->queryRowsK("SHOW COLUMNS FROM " . $table);
        foreach ($rows as $keyRow => $row) {
            $this->correctData($table, $row, $columns);
            if (!$fields) {
                $fields = " (`" . join("`, `", array_keys($row)) . "`) ";
            }
            $rows[$keyRow] = "(" . join(", ", $row) . ")";
        }
        $this->query("INSERT INTO " . $table . $fields . " VALUES " . join(", ", $rows));
        return $this->affectedRows();
    }

    /**
     * @param string $table
     * @param array  $rows
     *
     * @return int
     * @throws
     */
    public function insertOrUpdateRows($table, $rows = [])
    {
        if (!$rows) {
            return 0;
        }
        $fields  = "";
        $columns = $this->queryRowsK("SHOW COLUMNS FROM " . $table);
        foreach ($rows as $keyRow => $row) {
            $this->correctData($table, $row, $columns);
            if (!$fields) {
                $keys = $keysUpd = array_map(function($key) { return "`" . $key . "`"; }, array_keys($row));
                $fields  = " (" . join(", ", $keys) . ") ";
                if ($key = array_search("`created`", $keysUpd)) {
                    unset($keysUpd[$key]);
                }
            }
            $rows[$keyRow] = "(" . join(", ", $row) . ")";
        }
        if (!$keysUpd) {
            var_dump($table, $rows);
            exit;
        }
        $this->query("
            INSERT INTO " . $table . $fields . "
            VALUES " . join(", ", $rows) . "
                ON DUPLICATE KEY UPDATE " . join(", ", array_map(function($key) { return $key . " = VALUES(" . $key . ")"; }, $keysUpd))
        );
        return $this->affectedRows();
    }

    /**
     * @param  string $table
     * @param  array  $data
     * @param  string $idField
     *
     * @return int lastInsertId
     * @throws
     */
    public function insertOrUpdateRow($table, $row, $keyIds = ["id"])
    {
        $id = empty($row) || empty($keyIds) || empty($row[$keyIds[0]]) ? 0 : intval($row[$keyIds[0]]);
        if (isset($row["_updated"])) {
            unset($row["_updated"]);
        }
        $this->correctData($table, $row);
        $keys = $keysUpd = array_map(
            function ($key) {
                return "`" . $key . "`";
            },
            array_keys($row)
        );

        if ($key = array_search("`created`", $keysUpd)) {
            unset($keysUpd[$key]);
        }
        $this->query("
            INSERT INTO " . $table . " (" . join(", ", $keys) . ")
            VALUES (" . join(", ", $row) . ")
                ON DUPLICATE KEY UPDATE " . join(", ", array_map(
                    function($key) {
                        return $key . " = VALUES(" . $key . ")";
                    },
                    $keysUpd
                )
            )
        );
        return empty($id) ? $this->lastInsertID() : $id;
    }

    /**
     * @return int
     */
    public function lastInsertID()
    {
        return $this->myDb->insert_id;
    }

    /**
     * @return int
     */
    public function affectedRows()
    {
        return $this->myDb->affected_rows;
    }

    /**
     * @param  string $sql
     * @return int
     * @throws
     */
    public function execute($sql)
    {
        $this->query($sql);
        return $this->affectedRows();
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
        if (empty($this->myDb)) {
            $this->open();
        }
        $this->myDb->autocommit(false);
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
        if (true != $this->myDb->commit()) {
            if (true != $this->myDb->rollback()) {;
            throw new Exception("rollback - fail");
            }
            throw new Exception("commit transaction - fail");
        }
        $this->myDb->autocommit(true);
        $this->transactions --;
        return true;
    }

    /**
     * @return true
     * @throws
     */
    public function rollback()
    {
        if (true != $this->myDb->rollback()) {;
        throw new Exception("rollback - fail");
        }
        $this->transactions = 0;
        return true;
    }

    public function disconnect()
    {
        $this->myDb->close();
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
    static protected function makeString($template, $row, $escape = true)
    {
        if (!is_array($row)) {
            return "";
        }
        $search  = array ();
        $replace = array ();
        foreach ($row as $key => $value) {
            $search[]  = "#" . $key . "#";
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
        $this->myDb->ping();
    }

    /**
     * @return bool
     * @throws
     */
    protected function checkQueryResult($result = true, $sql = "")
    {
        if (false === $result or $this->myDb->error and $this->myDb->errno) {
            $error = $this->myDb->error;
            $errno = $this->myDb->errno;
            $request   = empty($_SERVER["REQUEST_URI"]) ? "" : "request: " . $_SERVER["REQUEST_URI"] . ($_POST ? "?" . http_build_query($_POST) : "") . "\n\n";
            $exception = new Exception($request . $sql . " [" . $error . "] - " . $errno);
            if ("" == $request and $this->numErrors < 5 and !$this->transactions) {
                // при offline запуске и отсутствии открытой транзакции
                // попытаемся переконнектится и выполнить запрос еще раз
                $this->numErrors ++;
                $this->close();
                sleep(70);
                return false;
            }
            $this->addLogs($exception);
            if ($this->transactions and true === $this->myDb->rollback()) {
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
    private function addLogs($exception)
    {
        $errorlog = new Logs("MySqlError");
        $errorlog->add($exception);
        $errorlog->add(var_export($this->myDb->info, true));
        $this->logs->add($exception);

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
        $telegram->sendMessage($exception, "MySql");
    }

    /**
     * @param  string $sql
     * @param  string $type
     * @return array|bool
     */
    private function getCache($sql, $type)
    {
        if ($this->useCache) {
            $md5 = md5($sql);
            if (isset($this->cache[$type][$md5])) {
                return $this->cache[$type][$md5];
            }
        }
        return false;
    }

    /**
     * @param string $sql
     * @param string $type
     * @param array  $data
     */
    private function setCache($sql, $type, $data)
    {
        if ($this->useCache) {
            if (!isset($this->cache[$type])) {
                $this->cache[$type] = array ();
            }
            $this->cache[$type][md5($sql)] = $data;
        }
    }

    /**
     * приведение данных
     * @param string &$tableName
     * @param array  &$row
     */
    private function correctData($table, &$data, $columns = "")
    {
        $table = $this->escapeTable($table);
        if (!$columns) {
            $columns  = $this->queryRowsK("SHOW COLUMNS FROM " . $table);
        }
        if (isset($columns["created"]) and empty($data["created"])) {
            $data["created"] = "NOW()";
        }
        $colsKeys = array_keys($columns);
        foreach ($data as $key => $val) {
            if (!$key or !in_array($key, $colsKeys)) {
                unset($data[$key]);
                continue;
            }
            if (in_array((string)$val, array ("NULL", "NOW()", "RAND()"))) {
                continue;
            }
            if (in_array($columns[$key]["Type"], array ("bit(1)"))) {
                continue;
            }
            $data[$key] = "'" . $this->escapeString($val) . "'";
        }
    }

    /**
     * string $table
     * array  $conditions
     * string $orderBy
     * int    $offset
     * int    $limit
     * string $addTable
     *
     * @return array
     * @throws
     */
    public function selectRows($table, $conditions = [], $orderBy = "name", $offset = 0, $limit = 0, $addTable = "") {
        if (is_array($table)) {
            extract($table);
        }
        $table = $this->escapeTable($table);
        $sql = "SELECT ";
        if (!empty($distinct) or !empty($addTable)) {
            $sql .= "DISTINCT ";
        }
        $sql .= $table . ".* FROM " . $table;
        if (!empty($addTable)) {
            $sql .= $addTable;
        }
        if ($conditions) {
            $conditionsNew = array ();
            foreach ($conditions as $key => $condition) {
                if (is_numeric($key)) {
                    $conditionNew = $condition;
                } else {
                    $conditionNew = "`" . $this->escapeString($key) . "` = '" . $this->escapeString($condition) . "'";
                }
                $conditionsNew[] = $conditionNew;
            }
            $sql .= " WHERE " . join(" AND ", $conditionsNew);
        }
        if ($orderBy) {
            $sql .= " ORDER BY " . $orderBy;
        }
        if ($limit) {
            $sql .= " LIMIT " . $limit;
            if ($offset) {
                $sql .= " OFFSET " . $offset;
            }
        }
        return $this->queryRows($sql);
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

        $sql = "SELECT ";
        $sql .= $table . ".* FROM " . $table;
        if (!empty($addTable)) {
            $sql .= $addTable;
        }
        if ($conditions) {
            $conditionsNew = array ();
            foreach ($conditions as $key => $condition) {
                if (is_numeric($key)) {
                    $conditionNew = $condition;
                } else {
                    $conditionNew = "`" . $this->escapeString($key) . "` = '" . $this->escapeString($condition) . "'";
                }
                $conditionsNew[] = $conditionNew;
            }
            $sql .= " WHERE " . join(" AND ", $conditionsNew);
        }
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        $sql .= " LIMIT 1";
        return $this->queryRow($sql);
    }

    /**
     * @param  string $string
     * @return string
     */
    public function escapeString($string)
    {
        if (empty($this->myDb)) {
            $this->open();
        }
        return $this->myDb->real_escape_string($string);
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
            if (in_array($val, array ("NULL", "NOW()"))) {
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
        $tableName = str_replace("`", "", $tableName);
        $escapedRows = array ();
        foreach (explode(".", $tableName) as $name) {
            $escapedRows[] = "`" . $this->escapeString($name) . "`";
        }
        $tableName = implode(".", $escapedRows);
        return $tableName;
    }
}
