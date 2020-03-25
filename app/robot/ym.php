<?php
/**
 * @category robot
 * @package  market.yamdex.ru
 * @author   <first@mail.ru>
 * @since    2019-10-31
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

    $ymRows     = $mySql->selectRows("ym", ["active" => 1]);
    foreach ($ymRows as $ymRow) {
        sleep(10);
        $htmlObj    = $browser->requestHtml($ymRow["url"]);
        if (!$htmlObj) {
            throw new Exception("{$ymRow["name"]}: error in {$ymRow["url"]}");
        }
        $divList    = $htmlObj->find("div.n-snippet-list", 0);
        if (!$divList) {
            throw new Exception("{$ymRow["name"]}: not found div list");
        }
        $price  = 0;
        $curr   = "";
        $divPrice   = false;
        $cnt    = 0;
        foreach ($divList->find("div.n-snippet-card") as $divCard) {
            $cnt ++;
            $divShopInfo    = $divCard->find("div.snippet-card__shop-info", 0);
            $spanDelivery   = $divCard->find("span.n-delivery__feature", 0);
            if ("Бесплатный самовывоз" != $spanDelivery->plaintext and "Оплата наличными. Самовывоз Бесплатно." != $divShopInfo->plaintext) {
                continue;
            }

            $divPrice       = $divCard->find("div.snippet-card__price", 0);
            $matches = [];
            if (preg_match('/^(\d+)(\S+)/', preg_replace('/\s+/', "", $divPrice->plaintext), $matches)) {
                $price  = $matches[1];
                $curr   = $matches[2];
            }
            break;
        }
        if (!$cnt) {
            $logs->add("{$ymRow["name"]}: not found div.n-snippet-card");
            foreach ($divList->find("div.n-product-top-offer") as $divProduct) {
                $cnt ++;
                $spanDelivery   = $divProduct->find("span.n-delivery__feature", 0);
                if ("Бесплатный самовывоз" != $spanDelivery->plaintext) {
                    continue;
                }

                $metaPrice  = $divProduct->find("meta[itemprop=price]", 0);
                $metaCurr   = $divProduct->find("meta[itemprop=priceCurrency]", 0);
                if ($metaPrice and $metaCurr) {
                    $price  = $metaPrice->content;
                    $curr   = $metaCurr->content;
                    break;
                }
            }
            if (!$cnt) {
                $logs->add("{$ymRow["name"]}: not found div.n-product-top-offer");
                sleep(30);
            }
        }
        if (!$price) {
            if (empty($divPrice->plaintext)) {
                $logs->add("{$ymRow["name"]}: not found div.snippet-card__price in {$ymRow["url"]}");
            } else {
                $logs->add("{$ymRow["name"]}: not found price in {$divPrice->plaintext}");
            }
            continue;
        }
        $logs->add("{$ymRow["name"]}: {$price} {$curr}");

        $ymLogRow = $mySql->selectRow("ym_log", ["active" => 1, "id_ym" => $ymRow["id"]], "", "`created` DESC");
        if ($ymLogRow) {
            if ($ymLogRow["price"] == $price) {
                continue;
            }
        }
        $ymLogRow = [
            "id_ym" => $ymRow["id"],
            "price" => $price,
            "curr"  => $curr
        ];
        $mySql->insertRow("ym_log", $ymLogRow);

        $ymUpdRow = [
            "id" => $ymRow["id"],
            "price" => $price,
            "curr"  => $curr
        ];
        $mySql->insertOrUpdateRow("ym", $ymUpdRow);

        if ($ymRow["telegram"]) {
            $telegram->sendMessage("{$price} {$curr}", $ymRow["name"], $ymRow["telegram"]);
        }
    }

} catch (Exception $e) {
    $logs->add($e);
}

$lock->delLock();
