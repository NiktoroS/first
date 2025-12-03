<?php
use app\inc\BrowserClass;
use app\inc\Lock;
use app\inc\Logs;
use app\inc\Storage\MySqlStorage;

/**
 * @category robot
 * @package  xe.com
 * @author   <mail@mail.ru>
 * @since    14.03.2014
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "Logs.php");
require_once(INC_DIR . "Lock.php");
require_once(INC_DIR . "BrowserClass.php");
require_once(INC_DIR . "Storage/MySqlStorage.php");

error_reporting(E_ALL);
sleep(0);

$logs = new Logs();
$lock = new Lock();

if (false === $lock->setLock()) {
    $logs->add("error in set lock");
    exit;
}


$dsnMySql["host"] = "sro.sro-abc.ru";
$dsnMySql["name"] = "sro";

try {
    $browser = new BrowserClass(); //"socks5://127.0.0.1:9050");

    $vals = [];
    for ($i = 0; $i < 3; $i ++) {
        sleep($i * 5);
        $html  = $browser->requestHtml("http://www.xe.com/currencytables/?from=RUB");
        if (!$html) {
            throw new Exception("empty html");
        }
        $table = $html->find("#historicalRateTbl", 0);
        if (!$table) {
            throw new Exception("not found table[id=historicalRateTbl]");
        }
        $val   = [];
        foreach ($table->find("tr") as $tr) {
            $td = $tr->find("td", 0);
            if (!$td) {
                continue;
            }
            $tdVal = $tr->find("td", 3);
            $val[$td->plaintext] = $tdVal->plaintext;
        }
        $vals[] = $val;
    }

    usort($vals, function($a, $b) { return $b["USD"] > $a["USD"]; });

#    $logs->add(var_export($vals, true));

    $data = $vals[1];
    $data["created"] = date("YmdHi00");

    $mySql = new MySqlStorage();

    $lastRow = $mySql->queryRow("SELECT * FROM `xe` WHERE `USD` <> 0 AND `EUR` <> 0 AND `GBP` <> 0 ORDER BY `created` DESC LIMIT 1");
    $data["d_USD"] = round($data["USD"] - $lastRow["USD"], 4);
    $data["d_EUR"] = round($data["EUR"] - $lastRow["EUR"], 4);
    $data["d_GBP"] = round($data["GBP"] - $lastRow["GBP"], 4);

    $data["EUR_USD"]   = round($data["EUR"] / $data["USD"], 8);
    $data["GBP_USD"]   = round($data["GBP"] / $data["USD"], 8);
    $data["d_EUR_USD"] = round($data["EUR_USD"] - $lastRow["EUR_USD"], 8);
    $data["d_GBP_USD"] = round($data["GBP_USD"] - $lastRow["GBP_USD"], 8);
/**
    $logs->add("lastRow:" . var_export($lastRow, true));
    $logs->add("data:" . var_export($data, true));
/**/
    $mySql->insertOrUpdateRow("xe", $data);
} catch (Exception $e) {
    $logs->add($e);
}

$lock->delLock();
