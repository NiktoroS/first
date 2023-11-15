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

    $PaymSubjTp = "18945";
    $params     = [];
    $step       = 0;

//    $param2 = rawurlencode(iconv("Utf-8", "Windows-1251", "Пупкин Пупок П."));
//    $get = "/?Amount=1500100&Params=1%2079160000000%3B2%20{$param2}%3B7%20af618dd535633baf31f46a9c7d681abe5a67d62ca50d5665abe185a58b76e075&PaymExtId={$requestId}&PaymSubjTp=6018&RequestId={$requestId}&TermID=FS001&TermType=006-22&function=Check&usr_mail=ABastina@alfabank.ru&user_ip=217.12.97.149&protocol_type=pip";
//    $get = "/?Amount=2500000&Params=1%2079160000000%3B2%20%C0%EB%E5%EA%F1%E0%ED%E4%F0%20%CF.%3B910%20680022%2C%20%D5%E0%E1%E0%F0%EE%E2%F1%EA%2C%20%C1%EE%EB%FC%F8%E0%FF%20103-%2C%2096%3B920%20%CF%E5%E4%F7%E5%ED%EA%EE%3B921%20%C0%EB%E5%EA%F1%E0%ED%E4%F0%3B925%20%3B7%20e8111b5ab85565f30b84eef08412a82259355228b274e1bb75198060524625a9&PaymExtId={$requestId}&PaymSubjTp=6018&RequestId={$requestId}&TermID=FS001&TermType=006-22&function=Check&usr_mail=ABastina@alfabank.ru&user_ip=217.12.97.149&protocol_type=pip";
//    $get = "/?Amount=2500000&Params=1%2079502030274%3B2%20%D1%E5%F0%E3%E5%E9%20%D8.%3B7%20df04a8ae08272412ed058cabdbfcf3c5ffef733d53267546bfea65c97cac60a9&PaymExtId={$requestId}&PaymSubjTp=6018&RequestId={$requestId}&TermID=FS001&TermType=006-22&function=Check&usr_mail=rsukhov@alfabank.ru&user_ip=217.12.103.126&protocol_type=pip";
/**/
    $get0 = "/?Params=&PaymSubjTp={$PaymSubjTp}&RequestId=&Step={$step}&TermID={$termId}&TermType=006-22&function=Prepare&usr_mail={$usr_mail}&user_ip={$user_ip}&protocol_type=pip";
    $result  = $browser->request("{$uri}{$get0}", "", $headerRows);
    sleep(1);

    $row = resultToRow($result);
    $requestId = $row["RequestId"];

    $step ++;
    $params[$step] = [
        1 => "01-000000067",
    ];

    $get1 = "/?" . paramStr($params[$step]) . "&PaymSubjTp={$PaymSubjTp}&RequestId={$requestId}&Step={$step}&TermID={$termId}&TermType=006-22&function=Prepare&usr_mail={$usr_mail}&user_ip={$user_ip}&protocol_type=pip";
    $result  = $browser->request("{$uri}{$get1}", "", $headerRows);
    sleep(1);

    $step ++;
    $params[$step] = [];

    $row = resultToRow($result);
    foreach ($row["Parameters"]["Next"]["Parameter"] as $paramRow) {
        $params[$step][$paramRow["@attributes"]["code"]] = $paramRow["Value"];
    }

    $get2   = "/?" . paramStr($params[$step]) . "&PaymSubjTp={$PaymSubjTp}&RequestId={$requestId}&Step={$step}&TermID={$termId}&TermType=006-22&function=Prepare&usr_mail={$usr_mail}&user_ip={$user_ip}&protocol_type=pip";
    $result  = $browser->request("{$uri}{$get2}", "", $headerRows);
    sleep(1);

    $row = resultToRow($result);
    if ($row["ErrCode"]) {
        var_dump($row);
        exit;
    }
/*
    $step ++;
    $params[$step] = [
#        14 => "4641",
//        15 => "11",
    ];
    $get3   = "/?" . paramStr($params[$step]) . "&PaymSubjTp={$PaymSubjTp}&RequestId={$requestId}&Step={$step}&TermID={$termId}&TermType=006-22&function=Prepare&usr_mail={$usr_mail}&user_ip={$user_ip}&protocol_type=pip";
    $result  = $browser->request("{$uri}{$get3}", "", $headerRows);
    sleep(1);

//    var_dump(resultToRow($result));

    sleep(1);
*/
    $extId      = mb_substr(md5(rand()), 0, 16);

    $paramsCheck = $params[1];

    $getCheck   = "/?Amount=376356&" . paramStr($paramsCheck) . "&PaymExtId={$extId}&PaymSubjTp={$PaymSubjTp}&RequestId={$requestId}&TermID={$termId}&TermType=006-22&function=Check&usr_mail={$usr_mail}&user_ip={$user_ip}&protocol_type=pip";
    $result  = $browser->request("{$uri}{$getCheck}", "", $headerRows);
    sleep(1);

    exit;
    $getPatment   = "/?Amount=376356&" . paramStr($paramsCheck) . "&PaymExtId={$extId}&PaymSubjTp={$PaymSubjTp}&RequestId={$requestId}&TermID={$termId}&TermType=006-22&function=Payment&usr_mail={$usr_mail}&user_ip={$user_ip}&protocol_type=pip";
    $result  = $browser->request("{$uri}{$getPatment}", "", $headerRows);

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

function paramStr($paramRows)
{
    $paramStr = "Params=";
    foreach ($paramRows as $key => $val) {
        $paramStr .= rawurlencode(iconv("UTF-8", "Windows-1251", "{$key} {$val};"));
    }
    return $paramStr;
}

function resultToRow($result)
{
    $row = json_decode(json_encode(simplexml_load_string($result)), true);
    if ($row["ErrCode"]) {
        var_dump($row);
        exit;
    }
    return $row;
}
