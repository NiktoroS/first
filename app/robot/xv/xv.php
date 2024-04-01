<?php
use app\inc\Lock;
use app\inc\Logs;
use app\inc\XvClass;

/**
 * @category xv
 * @package  xv
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    26.01.2017
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__, 2) . "/cnf/main.php");
require_once(INC_DIR . "Lock.php");
require_once(INC_DIR . "XvClass.php");

error_reporting(E_ALL);

$copies = 7;

$lock = new Lock();
if (false === ($copy = $lock->setLock($copies))) {
    exit;
}

$logs = new Logs();
$logs->setCopy($copy);

try {
    $logs->add("start");

    $xvObj = new XvClass();

    $tagRows = $xvObj->selectRows("tag", ["active" => 1, "`id` % {$copies} = {$copy}"], true, "`cnt_v` DESC");
    $logs->add("tagRows: " . count($tagRows));
    $cnt = 0;
    foreach ($tagRows as $tagKey => $tagRow) {
        if ($tagKey and 0 == $tagKey % 1000) {
            $logs->add("{$tagKey} {$cnt}");
        }
        $cnt += $xvObj->parceTag($tagRow);
    }
    $logs->add("finish: {$cnt}");
} catch (Exception $e) {
    var_dump($e);
    $logs->add($e);
}