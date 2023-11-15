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
    $uri = "http://test-inpaym.int.paymgate.ru"; //
    $requestId  = "TEST_1"; mb_substr(md5(rand()), 0, 16);
    $PaymExtId  = "TEST_1"; //mb_substr(md5(rand()), 0, 12);
    $paymSubjTp = "999782";
    $termType   = "006-22";
    $termId     = "US01";
    $step       = 0;
    $usr_mail   = "ca@uralsibbank.ru";
    $user_ip    = "195.234.190.15";

//    $requestId = "c52dfff539282d55";

    $paramRows = [];
    $browser = new Browser();
    $headerRows = [
        "User-Agent"    => "python-requests/2.24.00",
        "signature"     => "yv1vzDZGtmNo+m4p5hIOAdJkjBztml6Q3D3QZHwAV+Ezd50+4gMu70YYxtkeTLVBR/nPOJxXNDYNbRgA+3IbhA=="
    ];


//    $get0  = "/maskparams.php?function=prepare&step={$step}&RequestId=&PaymSubjTp={$paymSubjTp}&TermId={$termId}&TermType={$termType}&usr_mail={$usr_mail}&user_ip={$user_ip}";
//    $result  = $browser->request("{$uri}{$get0}", "", $headerRows);
/*
    $step ++;
    $paramRows[$step] = [
        1 => "1234123412341200",
        2 => "Петров Пётр",
        6 => "Тест",
    ];
//    $get1  = "/maskparams.php?" . paramStr($paramRows[$step]) . "&function=prepare&step={$step}&RequestId={$requestId}&PaymSubjTp={$paymSubjTp}&TermId={$termId}&TermType={$termType}&usr_mail={$usr_mail}&user_ip={$user_ip}";
//    $result  = $browser->request("{$uri}{$get1}", "", $headerRows);

    $paramRows["check"] = $paramRows[$step];
*/
    $check  = "/maskparams.php?function=Check&paymSubjTp=999786&termType=006-22&termID=US01&paymExtId=efc4c44d098548a9&params=1%201234123412341200;2%20Petrov%20Petr;6%20TestUS01&amount=1100&usr_mail=ca@uralsibbank.ru&user_ip=193.109.114.53";
    $result  = $browser->request("{$uri}{$check}", "", $headerRows);
    var_dump(resulttoRow($result));

    exit;
    $paramRows["payment"] = $paramRows["check"];

    $payment = "/maskparams.php?" . paramStr($paramRows["payment"]) . "&function=payment&Amount=1100&RequestId={$requestId}&PaymExtId={$PaymExtId}&PaymSubjTp={$paymSubjTp}&TermId={$termId}&TermType={$termType}&usr_mail={$usr_mail}&user_ip={$user_ip}";
    $result  = $browser->request("{$uri}{$payment}", "", $headerRows);

    var_dump($requestId, $result);
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

function resulttoRow($result)
{
    return json_decode(json_encode(simplexml_load_string($result)), true);
}
