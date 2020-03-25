<?php
/**
 * @category robot
 * @package  wooordhunt
 * @author   <first@mail.ru>
 * @since    2020-02-26
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "Logs.class.php");
require_once(INC_DIR . "Lock.class.php");
require_once(INC_DIR . "Telegram.class.php");
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
    $telegram   = new Telegram();

    $browser    = new Browser("socks5://127.0.0.1:9050");
    $url        = "https://www.osinavi.ru/my/8000.php";

    $htmlObj    = $browser->requestHtml($url);
    if (!$htmlObj) {
        throw new Exception("not get: {$url}");
    }
    $preObj = $htmlObj->find("pre", 0);
    if (!$preObj) {
        throw new Exception("not found: pre");
    }
    $text = $preObj->plaintext;
    foreach (explode("\r\n", $text) as $row) {
        $wdRow = explode(" -- ", $row);
        $row = [
            "language" => "en",
            "lenght"   => mb_strlen($wdRow[0]),
            "word"     => $wdRow[0],
            "description" => $wdRow[1],
        ];
        $rows[] = $row;
    }
//    $mySql->insertOrUpdateRows("dictionary", $rows);

} catch (Exception $e) {
    echo($e->getMessage());
    $logs->add($e);
}

$lock->delLock();
