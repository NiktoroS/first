<?php
use app\inc\BrowserClass;
use app\inc\Lock;
use app\inc\Logs;
use app\inc\Storage\MySqlStorage;
use app\inc\TelegramClass;

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

$array = [
    "l", "o", "d", "w", "u"
];

$lenght = 5;

function str_split_unicode($str, $l = 0) {
    if ($l > 0) {
        $ret = array();
        $len = mb_strlen($str, "UTF-8");
        for ($i = 0; $i < $len; $i += $l) {
            $ret[] = mb_substr($str, $i, $l, "UTF-8");
        }
        return $ret;
    }
    return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
}

try {
    $mySql      = new MySqlStorage($dsnMySql);
    $telegram   = new TelegramClass();

    $browser    = new BrowserClass("socks5://127.0.0.1:9050");
    $url        = "https://wooordhunt.ru/dic/content/en_ru";

    $htmlObj    = $browser->requestHtml($url);
    if (!$htmlObj) {
        throw new Exception("not get: {$url}");
    }
    $div = $htmlObj->find("div.cs_main", 0);
    foreach ($div->find("a") as $a) {
        if (!preg_match('/\/dic\/list\//', $a->href)) {
            continue;
        }
        $rows = [];
        $htmlObj2  = $browser->requestHtml("https://wooordhunt.ru" . $a->href);
        var_dump($a->href);
        foreach ($htmlObj2->find("p") as $p) {
            $a2 = $p->find("a", 0);
            if (!preg_match('/\/word\//', $a2->href)) {
                continue;
            }
            $word = mb_strtolower($a2->plaintext);
            $row = [
                "language" => "en",
                "lenght"   => mb_strlen($word),
                "word"     => $word,
                "description" => $p->plaintext,
            ];
            $rows[] = $row;
        }
        $mySql->insertOrUpdateRows("dictionary", $rows);
    }
} catch (Exception $e) {
    $logs->add($e);
}

$lock->delLock();
