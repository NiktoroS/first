<?php
/**
 * @category robot
 * @package  kinopoisk.ru
 * @author   <first@mail.ru>
 * @since    2019-10-20
 */
exit("Закрыт достут через tor + антипарсинговые какптчи");
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
$dateTo = date("Y-m-d", strtotime("-1 days"));

global $dsnMySql;

$dsnMySql["name"] = "sirotkin";

try {
    $browser    = new Browser("socks5://127.0.0.1:9050");
    $mySql      = new MySqlStorage($dsnMySql);
    $url        = "https://www.kinopoisk.ru/lists/top500/?tab=all";
    $mySql->query("UPDATE `kinopoisk_rate` SET `active` = 0 WHERE `date` > '{$dateTo}'");
    if (1 == date("j")) {
        $dateTo = '2007-01-01';
    }
    $n = 0;
    do {
        if (20 == $n) {
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
        $divListObj = $htmlObj->find("div.selection-list", 0);
        if (!$divListObj) {
            $logs->add("{$url} - empty div.selection-list");
            $n ++;
            sleep($n * 22);
            continue;
        }
        $n = 0;
        var_dump($divListObj->plaintext);
        foreach ($divListObj->find("div.selection-list__film") as $divItemObj) {
            $pName          = $divItemObj->find("p.selection-film-item-meta__name", 0);
            $pNameOrigin    = $divItemObj->find("p.selection-film-item-meta__original-name", 0);
            $spanRate       = $divItemObj->find("span.rating__value", 0);
            $spanPlace      = $divItemObj->find("span.selection-film-item-position", 0);
            $aId            = $divItemObj->find("a.selection-film-item-meta__link", 0);
            $spanVoites     = $divItemObj->find("span.rating__count", 0);

            $out = [];
            if (preg_match('/\/film\/(\d+)\//', $aId->href, $out)) {
                $idFilm = $out[1];
            } else {
                continue;
            }
            $filmRows[] = [
                "id"            => $idFilm,
                "name"          => $pName->plaintext,
                "name_origin"   => $pNameOrigin->plaintext,
                "place"         => $spanPlace->plaintext,
                "rate"          => $spanRate->plaintext,
            ];
            $rateRows[] = [
                "id_film"   => $idFilm,
                "date"      => $date,
                "rate"      => $spanRate->plaintext,
                "place"     => $spanPlace->plaintext,
                "voites"    => intval(preg_replace(['/\(/', '/\)/', '/&nbsp;/', '/\s+/'], ['', '', '', ''], $spanVoites->plaintext)),
                "active"    => 1,
            ];
        }
        $logs->add("{$date}: {$url}: " . count($filmRows) . ": " . count($rateRows));
        if ($filmRows) {
            $mySql->insertOrUpdateRows("kinopoisk_film", $filmRows);
        }
        if ($rateRows) {
            $mySql->insertOrUpdateRows("kinopoisk_rate", $rateRows);
        }

        $url = "";
        foreach ($htmlObj->find("a.paginator__page-relative") as $aNext) {
            if ("Вперёд" == $aNext->plaintext) {
                $url = "https://www.kinopoisk.ru" . html_entity_decode($aNext->href);
                break;
            }
        }
        $logs->add($url);
    } while($url);

} catch (Exception $e) {
    $logs->add($e);
}

$lock->delLock();
