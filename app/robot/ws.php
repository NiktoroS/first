<?php
/**
 * @category robot
 * @package  ws solve
 * @author   <first@mail.ru>
 * @since    2023-12-11
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "WsClass.php");

error_reporting(E_ALL);

try {
    $wsObj  = new WsClass();
    $wsObj->setLevel(isset($_SERVER["argv"][1]) ? $_SERVER["argv"][1] : 2);
    print_r($wsObj->solve());
} catch (Exception $e) {
    print_r($e);
}

