<?php
/**
 * @category robot
 * @package  rbc.ru
 * @author   <first@mail.ru>
 * @since    2018-03-22
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "Logs.class.php");
require_once(INC_DIR . "Lock.class.php");
require_once(INC_DIR . "Browser.class.php");
require_once(INC_DIR . "Storage/MySqlStorage.php");

error_reporting(E_ALL);

$logs = new Logs();
$lock = new Lock();

if (false === $lock->setLock()) {
    $logs->add("error in set lock");
    exit;
}
$date   = date("Y-m-d");
$dateTo = date("Y-m-d", strtotime("-14 days"));

global $dsnMySql;

$dsnMySql["name"] = "sirotkin";

try {
    $browser    = new Browser("socks5://127.0.0.1:9050");
    $mySql      = new MySqlStorage($dsnMySql);
    $url        = "https://www.kinopoisk.ru/top";
    $mySql->query("UPDATE `kinopoisk_rate` SET `active` = 0 WHERE `date` > '{$dateTo}'");
    if (1 == date("j")) {
        $dateTo = '2007-01-01';
    }
    $n = 0;
    do {
        if (10 == $n) {
            break;
        }
        $filmRows   = [];
        $rateRows   = [];

        $out = [];
        if (preg_match('/\/(\d{4}\-\d{2}\-\d{2})/', $url, $out)) {
            $date   = $out[1];
        }
        $htmlObj    = $browser->requestHtml($url);
        if (!$htmlObj) {
            $logs->add("{$url} - empty html");
            $n ++;
            sleep($n * 50);
            continue;
        }
        $tableMainObj   = $htmlObj->find("table.js-rum-hero", 0);
        if (!$tableMainObj) {
            $logs->add("{$url} - empty tableMain");
            $n ++;
            sleep($n * 50);
            continue;
        }
        $n = 0;
        $tableObj     = $tableMainObj->find("table", 0);
        foreach ($tableObj->find("tr") as $trObj) {
            $tdRow = [];
            foreach ($trObj->find("td") as $tdKey => $tdObj) {
                if (0 == $tdKey) {
                    if ("th" == $tdObj->class) {
                        break;
                    }
                }
                $tdRow[$tdKey] = $tdObj;
            }
            if (!$tdRow) {
                continue;
            }
            if (!isset($tdRow[1])) {
                break;
            }

            $aPlaceObj  = $tdRow[0]->find("a", 0);
            $aObj       = $tdRow[1]->find("a.all", 0);
            $spanObj    = $tdRow[1]->find("span.text-grey", 0);
            $divIdObj   = $tdRow[3]->find("div.shortestselect", 0);
            $aRateObj   = $tdRow[2]->find("a.continue", 0);
            $spanVoObj  = $tdRow[2]->find("span", 0);

            $idFilm     = $divIdObj->mid;
            $filmRows[] = [
                "id"            => $idFilm,
                "name"          => $aObj->plaintext,
                "name_origin"   => $spanObj ? $spanObj->plaintext : "",
                "place"         => $aPlaceObj->name,
                "rate"          => $aRateObj->plaintext,
            ];
            $rateRows[] = [
                "id_film"   => $idFilm,
                "date"      => $date,
                "rate"      => $aRateObj->plaintext,
                "place"     => $aPlaceObj->name,
                "voites"    => intval(preg_replace(['/\(/', '/\)/', '/&nbsp;/'], ['', '', ''], $spanVoObj->plaintext)),
                "active"    => 1,
            ];
        }
        $logs->add("{$date}:" . count($filmRows) . ":" . count($rateRows));

        if ($filmRows) {
            $mySql->insertOrUpdateRows("kinopoisk_film", $filmRows);
        }
        if ($rateRows) {
            $mySql->insertOrUpdateRows("kinopoisk_rate", $rateRows);
        }

        $url = "";
        foreach ($htmlObj->find("a.all") as $aObj) {
            if ("предыдущий день" == $aObj->plaintext) {
                $url = "https://www.kinopoisk.ru{$aObj->href}";
                break;
            }
        }
        $logs->add($url);
    } while($url and $date >= $dateTo);

} catch (Exception $e) {
    $logs->add($e);
}

$lock->delLock();
