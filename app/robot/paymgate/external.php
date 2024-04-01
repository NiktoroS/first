<?php
use app\inc\BrowserClass;
use app\inc\Logs;

/**
 * @category
 * @package  paymgate
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    09.03.2021
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__, 2) . "/cnf/main.php");
require_once(INC_DIR . "Logs.php");
require_once(INC_DIR . "BrowserClass.php");

error_reporting(E_ALL);

$logs = new Logs();

try {
    $logs->add("start");
//    $uri = "https://agentgate.secgw.ru/regions/1/cards/ips/TRANSPORT/D49A8D31594D997D8EF2F9A33768F26C5B13F3ED/available-operation";
    $uri = "https://test-agentgate.secgw.ru/regions/01027/cards/ips/TRANSPORT/D49A8D31594D997D8EF2F9A33768F26C5B13F3ED/available-operations";
    $headerRows = [
        "Content-type" => "application/json; charset=utf-8",
        "Accept" => "application/json",
        "AGENTID" => "8001",
        "X-SSL-CLIENT-THUMBPRINT" => "75540ea5eae9cb9efaf4d3daefeb95f29d44a1e3",
//        "X-SSL-CLIENT-THUMBPRINT" => "e3559ca0c07b69d292a16329a1bcb2861f69c886",
//        "Authorization" => "Basic U2JlcmFnZW50OkFFSGUzR1BUbUJETA==",
    ];

    $browser = new BrowserClass();

    $curlRows = [
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 35,
        CURLINFO_HEADER_OUT => 1,
        CURLOPT_HEADER => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FAILONERROR => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPAUTH => 1,
//        CURLOPT_USERPWD => "Sberagent:AEHe3GPTmBDL",
        CURLOPT_USERPWD => "nkotest:YHHvdeHgqw",
        CURLOPT_SSLKEY  => "/mnt/c/int/it/development/services/outpaym/x509/TSP/16033/nko.key",
        CURLOPT_SSLCERT => "/mnt/c/int/it/development/services/outpaym/x509/TSP/16033/nko.cer",
    ];

    $browser->request($uri, "GET", $headerRows, [], true, $curlRows);
    var_dump($uri, $browser->res, $browser->resHeader);
} catch (Exception $e) {
    var_dump($e);
    $logs->add($e);
}