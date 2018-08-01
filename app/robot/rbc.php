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
sleep(0);

$logs = new Logs();
$lock = new Lock();

if (false === ($copy = $lock->setLock())) {
    $logs->add("error in set lock");
    exit;
}
/**
 * USD
 * https://quote.rbc.ru/exchanges/info/selt.0/59111/delay
 * https://quote.rbc.ru/data/graph/delay/selt.0/59111/history/?tickerid=59111&resolution=D&from=1490627899&to=1521731959
 * https://quote.rbc.ru/data/ticker/graph/72413/D?_=1527539370824
 *
 * EUR
 * https://quote.rbc.ru/exchanges/info/selt.0/59090/delay
 * https://quote.rbc.ru/data/graph/delay/selt.0/59090/history/?tickerid=59090&resolution=D&from=1490628201&to=1521732262
 *
 * USD, ЦБ
 * https://quote.rbc.ru/exchanges/info/cb.0/72413/eod
 * https://quote.rbc.ru/data/graph/eod/cb.0/72413/history/?tickerid=72413&resolution=D&from=1490691417&to=1521795477
 * EUR, ЦБ
 * https://quote.rbc.ru/exchanges/info/cb.0/72383/eod
 *
 * brand
 * https://quote.rbc.ru/exchanges/info/ipe.0/83350/delay
 * https://quote.rbc.ru/data/graph/delay/ipe.0/83350/history/?tickerid=83350&resolution=D&from=1490628380&to=1521732440
 *
 * BTC / USD
 * https://www.rbc.ru/crypto/currency/btcusd
 * https://www.rbc.ru/crypto/data/graph/166026/day/3/?_=1527540691238
 */

$days = 1;
if (isset($_SERVER["argv"][1])) {
    $days = $_SERVER["argv"][1];
}

$quoteRows = array (
    "USD"    => array ("selt", 59111, "delay", "1"),
    "EUR"    => array ("selt", 59090, "delay", "1"),
    "USD_CB" => array ("cb", 72413, "eod", "D"),
    "EUR_CB" => array ("cb", 72383, "eod", "D"),
    "brand"  => array ("ipe",  181206, "delay", "1"),
);

$dsnMySql["host"] = "sro.sro-abc.ru";

//https://quote.rbc.ru/data/ticker/graph/72413/D?_=1527539370824
//https://quote.rbc.ru/data/ticker/graph/59111/D?_=1527534642481
//var_dump(date("Y-m-d H:i:s", 1527536040/*1527536100*/));
try {
    $browser = new Browser("socks5://127.0.0.1:9050");
    $mySql   = new MySqlStorage;
    $rbcRows = array ();

    foreach ($quoteRows as $var => $vals) {
        $html  = $browser->request("https://quote.rbc.ru/exchanges/info/" . $vals[0] . ".0/" . $vals[1] . "/" . $vals[2]);
        foreach(array ("D", "W", "M") as $band) {
            $url   = "https://quote.rbc.ru/data/ticker/graph/" . $vals[1] . "/" . $band . "?_=" . intval(microtime(true) * 1000);
            var_dump($url);
            $json  = $browser->request($url);
            $array = json_decode($json, true);
            $logs->add($var . ": " . $band . ": " . count($array["result"]["data"]));
            if (empty($array["result"]["data"])) {
#                var_dump($array);
                continue;
            }
            var_dump(count($array["result"]["data"]));
            foreach ($array["result"]["data"] as $row) {
                if (empty($rbcRows[$row[3]])) {
                    $rbcRows[$row[3]] = array ("created" => date("Y-m-d H:i:s", $row[3] / 1000));
                }
                if ($row[4]) {
                    $rbcRows[$row[3]][$var] = $row[4];
                } elseif ($row[6]) {
                    $rbcRows[$row[3]][$var] = $row[6];
                }
            }
        }
    }
    ksort($rbcRows);
    foreach ($rbcRows as $rbcRow) {
        $mySql->insertOrUpdateRow("rbc", $rbcRow, "created");
    }
} catch (Exception $e) {
    $logs->add($e);
}

$lock->delLock();
