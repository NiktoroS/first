<?php

use app\inc\BrowserClass;
use app\inc\Lock;
use app\inc\Logs;
use app\inc\Storage\MySqlStorage;

/**
 * @category robot
 * @package  wooordhunt
 * @author   <first@mail.ru>
 * @since    2020-02-26
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "Logs.php");
require_once(INC_DIR . "Lock.php");
require_once(INC_DIR . "TelegramClass.php");
require_once(INC_DIR . "Storage/MySqlStorage.php");

error_reporting(E_ALL);

$logs = new Logs();
$lock = new Lock();

if (false === $lock->setLock()) {
    $logs->add("error in set lock");
    exit;
}

global $dsnMySql;

$dsnMySql["name"] = "sirotkin";


try {
    $mySql      = new MySqlStorage($dsnMySql);
//    $telegram   = new TelegramClass();

    $browser    = new BrowserClass();//"socks5://127.0.0.1:9050");
    $url        = "https://www.ef.ru/angliyskie-resursy/spiski-angliyskih-slov/1000-slov/";

    $htmlObj    = $browser->requestHtml($url);
    if (!$htmlObj) {
        throw new Exception("not get: {$url}");
    }
    $divObj = $htmlObj->find("div.content", 0);
    if (!$divObj) {
        throw new Exception("not found: div.content");
    }
    $pObj = $divObj->find("p", 1);
    foreach (explode("\n", $pObj->plaintext) as $word) {
        $word = trim($word);
        $row = [
            "language" => "en",
            "lenght"   => mb_strlen($word),
            "word"     => $word,
        ];
        $rows[] = $row;
    }
    $mySql->insertOrUpdateRows("dictionary", $rows);

} catch (Exception $e) {
    echo($e->getMessage());
    $logs->add($e);
}

$lock->delLock();
