<?php
use app\inc\Lock;
use app\inc\Logs;
use app\inc\XvClass;

/**
 * @category mc
 * @package  xv
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    26.01.2017
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__, 2) . "/cnf/main.php");
require_once(INC_DIR . "XvClass.php");
require_once(INC_DIR . "Lock.php");

error_reporting(E_ALL);

$copies = 1;

$lock = new Lock();
if (false === ($copy = $lock->setLock($copies))) {
    exit;
}

$logs = new Logs();
$logs->setCopy($copy);

$cnt = 0;
try {
    $logs->add("start");
    $xvObj    = new XvClass();
    $htmlObj  = $xvObj->requestHtml();
    $mcDivObj = $htmlObj->find("div.main-categories", 0);
    foreach ($mcDivObj->find("a.btn") as $key => $aObj) {
        if ($key % $copies != $copy) {
            continue;
        }
        $cnt += $xvObj->parceMc($aObj->href);
    }
    $logs->add("finish : {$cnt}");
} catch (Exception $e) {
    $logs->add($e);
}