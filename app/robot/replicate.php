<?php
/**
 * @category robot
 * @package  replicate
 * @author   <first@mail.ru>
 * @since    2018-04-10
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "Logs.class.php");
require_once(INC_DIR . "Lock.class.php");
require_once(INC_DIR . "Storage/MySqlStorage.php");

error_reporting(E_ALL);
sleep(0);

$logs = new Logs();
$lock = new Lock();

if (false === ($copy = $lock->setLock())) {
    $logs->add("error in set lock");
    exit;
}

$dsnDst = $dsnMySql;

$dsnDst["host"]   = "sro.sro-abc.ru";

$dsnMySql["name"] = "sirotkin";

try {
    $mySqlSrc = new MySqlStorage;
    $mySqlDst = new MySqlStorage($dsnDst);
/*
    $tables = array (
        "rbc",
        "xe",
    );
    replicateTables($tables, $mySqlSrc, $mySqlDst);
*/
    $tables = array (
        "rbc",
        "xe",
        "electro"
    );
    replicateTables($tables, $mySqlDst, $mySqlSrc);
} catch (Exception $e) {
    $logs->add($e);
}

$lock->delLock();

function replicateTables($tables, $fromDb, $toDb)
{
    global $logs;
    $maxUpdated = "0000-00-00";
    $sql = "
        SELECT SUBTIME(MAX(res._updated), '02:00:00') FROM (
        SELECT MAX(`_updated`) AS `_updated` FROM `" . join ("` UNION ALL SELECT MAX(`_updated`) AS `_updated` FROM `", $tables) . "`) AS res
    ";
    $one = $toDb->queryOne($sql);
    if ($one and $one != "0000-00-00 00:00:00") {
        $maxUpdated = $one;
    }
    $logs->add($maxUpdated . " last update");

    foreach ($tables as $table) {
        $sqlArr = array ();
        $sql = "SELECT * FROM `" . $table . "` WHERE `_updated` >= '" . $maxUpdated . "' ORDER BY `_updated` ASC";
        $arr = $fromDb->queryRows($sql);
        if ($arr) {
            $num = 0;
            $logs->add(count($arr) . " - " . $table);

            $toDb->query("SET FOREIGN_KEY_CHECKS=0");
            $toDb->begin();
            $skipFields = array("_updated");
            $sql = "SHOW COLUMNS FROM `" . $table . "` WHERE `Field` NOT IN ('" . join ("', '", $skipFields). "')";
            $fieldsRows = $toDb->queryRows($sql);
            $insFields = array_map(function($row) { return $row["Field"]; }, $fieldsRows);
            $updFields = array_map(function($row) { return "`" . $row["Field"] . "` = VALUES(`" . $row["Field"] . "`)"; }, $fieldsRows);

            foreach (array_chunk($arr, 1000) as $arrPart) {
                $dataAll   = array ();
                foreach ($arrPart as $key => $row) {
                    $dataRow      = array ();
                    $dataItemsRow = array ();
                    foreach ($insFields as $field) {
                        $dataRow[$field] = is_null($row[$field]) ? "NULL" : "'" . $toDb->escapeString($row[$field]) . "'";
                    }
                    $dataAll[] = join(", ", $dataRow);
                }
                $sql = "INSERT INTO `" . $table . "` (`" . join("`, `", $insFields) . "`) VALUES (" . join("), (", $dataAll) . ") ON DUPLICATE KEY UPDATE " . join(", ", $updFields);
                $num += $toDb->execute($sql);
            }
            $toDb->commit();
            $toDb->query("SET FOREIGN_KEY_CHECKS=1");

            if ($num) {
                $logs->add($num . " - affected rows at " . $table);
            }
            unset($arr);
        }
    }
    $patchSql = array ();
    foreach ($patchSql as $sql) {
        $toDb->query($sql);
    }
}
