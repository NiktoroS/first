<?php

use app\inc\BrowserClass;
use app\inc\Logs;

/**
 * @category
 * @package  xv
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    02.03.2021
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "Logs.php");
require_once(INC_DIR . "BrowserClass.php");

error_reporting(E_ALL);

$logs = new Logs();

$url = "https://avia.tutu.ru/offers/?class=Y&passengers=100&route[0]=491-04082021-78&route[1]=78-18082021-491&changes=all";

try {
    $browser = new BrowserClass("socks5://127.0.0.1:9050");
    $browser->request("{$url}", "GET");

    echo($browser->res);
} catch (Exception $e) {
    var_dump($e);
    $logs->add($e);
}
