<?php
/**
 * @category profile
 * @package  xv
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    26.01.2017
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__, 2) . "/cnf/main.php");
require_once(INC_DIR . "Xv.class.php");
require_once(INC_DIR . "Lock.class.php");

error_reporting(E_ALL);

$copies = 1;

$lock = new Lock();
if (false === ($copy = $lock->setLock($copies))) {
    exit;
}

$logs = new Logs();
$logs->setCopy($copy);

try {
    $logs->add("start");
    $xvObj = new xvClass();

    $resultObj = $xvObj->queryForSelect("SELECT * FROM profile WHERE 1 = active AND id % {$copies} = {$copy} ORDER BY id DESC");
    $logs->add("profileRows: " . $resultObj->num_rows);
    $new = 0;
    $profileKey = 0;
    while ($profileRow = $resultObj->fetch_assoc()) {
        $profileKey ++;
        $new += $xvObj->parceProfile($profileRow);
        if ($profileKey and 0 == $profileKey % 10000) {
            $logs->add($profileKey . " " . $new);
        }
    }
    $logs->add("finish " . $new);
} catch (Exception $e) {
    $logs->add($e);
}