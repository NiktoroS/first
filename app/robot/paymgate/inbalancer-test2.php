<?php
use app\inc\BrowserClass;
use app\inc\Logs;

/**
 * @category
 * @package  paymgate
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    02.03.2021
 */

$requestFile = __DIR__ . "/request.txt";
$uri = "http://test-inbalancer.int.paymgate.ru";

if (isset($_SERVER["argv"][1])) {
    if (file_exists($requestFile)) {
        $result  = file_get_contents("{$uri}" . file_get_contents($requestFile));
        sleep(1);
        if (file_exists($requestFile)) {
            unlink($requestFile);
        }
    }
    exit;
}

set_time_limit(0);

require_once(dirname(__DIR__, 2) . "/cnf/main.php");
require_once(INC_DIR . "Logs.php");
require_once(INC_DIR . "BrowserClass.php");

error_reporting(E_ALL);

$logs = new Logs();


$usr_mail   = "sberbank@sberbank.ru";
$user_ip    = "127.0.0.1";
$termId     = "DOT6"; // "FS001";
$TermType   = "006-22";
$PaymSubjTp = "6043"; //18578";

$headerRows = [
    "User-Agent" => "python-requests/2.24.00",
];

try {

    $logs->add("start");

    $browser = new BrowserClass();

    $PaymExtId  = mb_substr(md5(rand()), 0, 12);
    $requestId  = md5(rand());


    $paramsCheck = [
        "1" => "79253566001",
        "2" => "Сироткин АА",
    ];


    $check   = "/?Amount=1200&" . paramStr($paramsCheck) . "&PaymExtId={$PaymExtId}&PaymSubjTp={$PaymSubjTp}&RequestId={$requestId}&TermID={$termId}&TermType={$TermType}&function=Check&usr_mail={$usr_mail}&user_ip={$user_ip}";
    $result  = $browser->request("{$uri}{$check}", "", $headerRows);
    sleep(1);
    $row = resultToRow($result);
    var_dump($row);
    exit;

    $payment = "/?Amount=1200&" . paramStr($paramsCheck) . "&PaymExtId={$PaymExtId}&PaymSubjTp={$PaymSubjTp}&RequestId={$requestId}&TermID={$termId}&TermType={$TermType}&function=Payment&usr_mail={$usr_mail}&user_ip={$user_ip}";

//    file_put_contents($requestFile, $payment);
    var_dump($payment);
    exit;
/*
    $result  = $browser->request("{$uri}{$payment}", "", $headerRows);
    $row = resultToRow($result);
    var_dump($row);

    usleep(10000);
    $result  = $browser->request("{$uri}{$payment}", "", $headerRows);
    $row = resultToRow($result);
    var_dump($row);
*/

    $procStr = "{$_SERVER["_"]} " . __FILE__. " '{$payment}'";
    var_dump($procStr);
    for ($i = 0; $i < 2; $i++) {
        $procRows[$i] = proc_open($procStr, $descriptorspec, $pipeRows[$i]);
        if (is_resource($procRows[$i])) {
            fclose($pipeRows[$i][0]);
        } else {
            echo("Не удалось запустить процесс {$i}. {$procStr}");
            unset($procRows[$i]);
            $pipeRows[$i] = [];
        }
    }

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

function resulttoRow($result)
{
    $row = json_decode(json_encode(simplexml_load_string($result)), true);
    if ($row["ErrCode"]) {
        var_dump($row);
        exit;
    }
    return $row;
}
