<?php
use app\inc\Lock;
use app\inc\Logs;
use app\inc\XvClass;

/**
 * @category profile_day
 * @package  xv
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    2017-03-14
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__, 2) . "/cnf/main.php");
require_once(INC_DIR . "Lock.php");
require_once(INC_DIR . "XvClass.php");

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

    $xvObj = new XvClass();

    $profileRows = $xvObj->selectRows("profile", ["active" => 1, "`_updated` > NOW() - INTERVAL 1 DAY"], true, "`_updated` ASC");
    $logs->add("profileRows: " . count($profileRows));
    $new = 0;
    foreach ($profileRows as $profileKey => $profileRow) {
        $new += $xvObj->parceProfile($profileRow);
        if ($profileKey and 0 == $profileKey % 100) {
            $logs->add($profileKey . " " . $new);
        }
    }
    $logs->add("finish " . $new);
} catch (Exception $e) {
    $logs->add($e);
}