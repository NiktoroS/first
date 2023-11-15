<?php
/**
 * @category
 * @package  xv
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    02.03.2021
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__, 2) . "/cnf/main.php");
require_once(INC_DIR . "Logs.class.php");
require_once(INC_DIR . "Browser.class.php");

error_reporting(E_ALL);

$logs = new Logs();

try {
    $logs->add("start");
    $uri = "http://localhost:830"; //
//    $uri = "http://test-inpaym.int.paymgate.ru";
//    $uri = "http://inpaym.int.paymgate.ru";

    $get = "/transport-card.php?function=one-click&paymSubjTp=16069&&usr_mail=paysupport@sberbank.ru&user_ip=194.186.207.246&requestId=" . md5(microtime(true));
//    $get = "/transport-card.php?function=status&paymSubjTp=16069&invoice=901390&&usr_mail=paysupport@sberbank.ru&user_ip=194.186.207.246";

//    $get = "/transport-card.php?function=available-operations&paymSubjTp=16069&cardSystem=transport&pan=9643109066600000508&&usr_mail=paysupport@sberbank.ru&user_ip=194.186.207.236";

//    $get = "/transport-card.php?function=status&paymSubjTp=16069&invoice=10893750&&usr_mail=paysupport@sberbank.ru&user_ip=194.186.207.236";
//    $get = "/transport-card.php?function=available-operations&paymSubjTp=16069&cardSystem=transport&pan=3106470000100184186&&usr_mail=paysupport@sberbank.ru&user_ip=194.186.207.236";

    $browser = new Browser();
    $headerRows = [
        "User-Agent" => "python-requests/2.24.00",
//        "Accept-Encoding" => "gzip, deflate",
//        "Accept" => "*/*",
//        "Connection" => "keep-alive",
//        "AutoPay" => "true",
//        "Content-Length" => "163",
//        "Content-Type" => "application/json",
//        "Authorization" => "Basic bmtvdGVzdDpZSEh2ZGVIZ3F3",
    ];
/**/
    $data = Array(
        "agentTransactionId" => rand(10000, 99999),
        "order" => Array(
            "orderCards" => Array(
                0 => Array(
                    "purchaseItems" => Array(
                        0 => Array(
                            "serviceId" => 11186, /*17047, 19013,*/
                            "usedCounterAmount" => 0
                        ),
                    ),
                    "transportCard" => Array(
                        "pan" => "9643109066600000508"
                    )
                )
            )
        )
    );
/**
    $data = [
        "agentTransactionId" => "97222",
        "invoiceStatus" => "PAID",
    ];
/**/
    $json = json_encode($data);
//    var_dump(json_decode($json, true));
//    exit;
    $result  = $browser->request("{$uri}{$get}", "POST", $headerRows, $json);
//    $result  = $browser->request("{$uri}{$get}", "", $headerRows);
    var_dump($result);
} catch (Exception $e) {
    var_dump($e);
    $logs->add($e);
}