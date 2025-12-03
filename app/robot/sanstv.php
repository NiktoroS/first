<?php
use app\inc\Lock;
use app\inc\Logs;
use app\inc\Storage\MySqlStorage;
use app\inc\TelegramClass;
use app\inc\BrowserClass;

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

    $browser    = new BrowserClass("socks5://127.0.0.1:9050");
    $url        = "https://sanstv.ru/english_words";

    $htmlObj    = $browser->requestHtml($url);
    if (!$htmlObj) {
        throw new Exception("not get: {$url}");
    }
    $tableObj = $htmlObj->find("table.hideChecked", 0);
    if (!$tableObj) {
        throw new Exception("not found: table.hideChecked");
    }
    foreach ($tableObj->find("tr") as $tr) {
        $td3 = $tr->find("td", 3);
        if (!$td3) {
            continue;
        }
        $a  = $td3->find("a.word", 0);
        if (!$a) {
            continue;
        }
        $td5 = $tr->find("td", 5);
        $row = [
            "language" => "en",
            "lenght"   => mb_strlen($a->plaintext),
            "word"     => $a->plaintext,
            "description" => $td5->plaintext,
        ];
        $rows[] = $row;
    }
    var_dump($rows);
    $mySql->insertOrUpdateRows("dictionary", $rows);

} catch (Exception $e) {
    echo($e->getMessage());
    $logs->add($e);
}

$lock->delLock();
