<?php
use app\inc\BrowserClass;
use app\inc\Lock;
use app\inc\Logs;
use app\inc\Storage\MySqlStorage;
use app\inc\TelegramClass;

/**
 * @category robot
 * @package  market.yamdex.ru
 * @author   <first@mail.ru>
 * @since    2019-10-31
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
    "t", "e", "h", "e", "r"
];

$lenght = 3;

if (isset($_SERVER["argv"][1])) {
    $array = str_split($_SERVER["argv"][1]);
}

if (isset($_SERVER["argv"][2])) {
    $lenght = $_SERVER["argv"][2];
}



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
    $telegram   = new TelegramClass();

    $mySql      = new MySqlStorage($dsnMySql);

    $rows       = $mySql->queryRowsK("SELECT `word` as `key`, `word` FROM `dictionary` WHERE `lenght` = {$lenght} ORDER BY 1");
    foreach ($rows as $row) {
        $letters = str_split($row);
        $arrayCur = $array;
        foreach ($arrayCur as $key => $char) {
            foreach ($letters as $lKey => $lVal) {
                if ($char == $lVal) {
                    unset($arrayCur[$key]);
                    unset($letters[$lKey]);
                    continue 2;
                }
            }
        }

        if (count($array) - count($arrayCur) <> $lenght) {
            unset($rows[$row]);
            continue;
        }
    }
    var_dump($rows);
exit;

    $browser    = new BrowserClass("socks5://127.0.0.1:9050");
    $url        = "https://ru.wiktionary.org/wiki/%D0%9A%D0%B0%D1%82%D0%B5%D0%B3%D0%BE%D1%80%D0%B8%D1%8F:%D0%A1%D0%BB%D0%BE%D0%B2%D0%B0_%D0%B8%D0%B7_3_%D0%B1%D1%83%D0%BA%D0%B2/en";

    do {
        $htmlObj    = $browser->requestHtml($url);
        if (!$htmlObj) {
            throw new Exception("not get: {$url}");
        }
        $rows = [];
        foreach ($htmlObj->find("div.mw-category-group") as $div) {
            foreach ($div->find("ul") as $ul) {
                foreach ($ul->find("li") as $li) {
                    $word = mb_strtolower($li->plaintext);
                    $row = [
                        "language" => "en",
                        "lenght"   => mb_strlen($word),
                        "word"     => $word,
                    ];
                    $rows[] = $row;
                }
            }
        }
        $url = "";
        foreach ($htmlObj->find("a") as $a) {
            if ("Следующая страница" == $a->plaintext) {
                $url = "https://ru.wiktionary.org" . str_replace("&amp;", "&", $a->href);
                break;
            }
        }
        $mySql->insertOrUpdateRows("dictionary", $rows);
        var_dump($url);
    } while ("" != $url);

} catch (Exception $e) {
    $logs->add($e);
}

$lock->delLock();
