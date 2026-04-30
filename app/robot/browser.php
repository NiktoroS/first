<?php

use app\inc\BrowserClass;

/**
 * @category    robot
 * @package     telegram
 * @author      <andrey.a.sirotkin@megafon.ru>
 * @since       2025-11-14
 */

set_time_limit(0);
ini_set("memory_limit", -1);

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "BrowserClass.php");

error_reporting(E_ALL);


$browser = new BrowserClass();

try {
    var_dump($browser->request("https://api.telegram.org"));
} catch (Exception $exception) {
    var_dump($exception->getMessage());
}