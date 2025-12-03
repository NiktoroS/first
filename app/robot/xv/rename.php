<?php
use app\inc\Logs;
use app\inc\XvClass;

/**
 * @category
 * @package  xv
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    18.01.2021
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__, 2) . "/cnf/main.php");
require_once(INC_DIR . "XvClass.php");

error_reporting(E_ALL);

$logs = new Logs();

try {
    $logs->add("start");

    $xvObj = new XvClass();
    $cnt   = $xvObj->rename();
    $logs->add("finish: {$cnt}");
} catch (Exception $e) {
    var_dump($e);
    $logs->add($e);
}