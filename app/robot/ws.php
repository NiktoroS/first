<?php


/**
 * @category robot
 * @package  ws solve
 * @author   <first@mail.ru>
 * @since    2023-12-11
 */

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "WsClass.php");

ini_set("memory_limit", -1);
error_reporting(E_ALL);
set_time_limit(0);

try {
    $wsObj = new WsClass();
    $wsObj->setLevel(isset($_SERVER["argv"][1]) ? $_SERVER["argv"][1] : 1100);
    print_r($wsObj->solve(!empty($_SERVER["argv"][2])));
} catch (Exception $e) {
    print_r($e);
}

