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

    $browser = new Browser();
    $headerRows = [
        "User-Agent" => "python-requests/2.24.00",
    ];

    $uri = "http://inbalancer.int.paymgate.ru"; //
    $usr_mail   = "ca@uralsibbank.ru";
    $user_ip    = "127.0.0.1";
    $termId     = "US01"; // "FS001";

    $PaymSubjTp = "6024";

    $requestId   = md5(rand());
    $extId       = substr(md5(rand()), 0, 10);
    $paramsCheck = [
        1 => "79778905046",
        2 => "Saveliy"
    ];

    $getCheck   = "/?Amount=1100&" . paramStr($paramsCheck) . "&PaymExtId={$extId}&PaymSubjTp={$PaymSubjTp}&RequestId={$requestId}&TermID={$termId}&TermType=006-22&function=Check&usr_mail={$usr_mail}&user_ip={$user_ip}&protocol_type=pip";
    $result     = $browser->request("{$uri}{$getCheck}", "", $headerRows);
    var_dump("{$uri}{$getCheck}");
    var_dump($result);
    sleep(1);

    exit;
    $getPatment = "/?Amount=1100&" . paramStr($paramsCheck) . "&PaymExtId={$extId}&PaymSubjTp={$PaymSubjTp}&RequestId={$requestId}&TermID={$termId}&TermType=006-22&function=Payment&usr_mail={$usr_mail}&user_ip={$user_ip}&protocol_type=pip";
    $result     = $browser->request("{$uri}{$getPatment}", "", $headerRows);

    var_dump($result);
/*
    $row = [
        "function" => "decrypt",
        "user_id" => "8001",
        "process" => "9",
        "ext_id" => "",
        "request_id" => "8e22d8260a09549d76f9fc47f2ecc4a2",
        "data" => "l3G16XZxIt1wcKR+ifu+umsryCqLFoJq"
    ];
    $result  = $browser->request("{$post}", "POST", $headerRows, $row);
*/

} catch (Exception $e) {
    var_dump($e);
    $logs->add($e);
}

//1249618261730
//1294007526038

function paramStr($paramRows)
{
    $paramStr = "Params=";
    foreach ($paramRows as $key => $val) {
        $paramStr .= rawurlencode(iconv("UTF-8", "Windows-1251", "{$key} {$val};"));
    }
    return $paramStr;
}

function resulttoRow($result)
{
    $row = json_decode(json_encode(simplexml_load_string($result)), true);
    if ($row["ErrCode"]) {
        var_dump($row);
        exit;
    }
    return $row;
}
