<?php
use app\inc\BrowserClass;
use app\inc\Logs;

/**
 * @category
 * @package  paymgate
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    08.07.2021
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__, 2) . "/cnf/main.php");
require_once(INC_DIR . "Logs.php");
require_once(INC_DIR . "BrowserClass.php");

error_reporting(E_ALL);

$logs = new Logs();

$argv = $_SERVER["argv"];
array_shift($argv);

$dir = __DIR__ . "/nspk";
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

try {
    $logs->add("start");
    $uri    = "https://sbp-gate3.nspk.ru/test/";
    $get    = join("/", $argv);// "start/00033"; //"main";//"test/start/00030";
    $file   = $dir . "/" . join("_", $argv) . ".html";
    $browser = new BrowserClass();
    $headerRows = [
        "User-Agent" => "curl",
    ];
    $curlRows = [
//        CURLOPT_HTTPHEADER  => ["Content-Type: application/xml; charset=utf-8"],
        CURLOPT_SSL_VERIFYPEER  => 0,
        CURLOPT_SSL_VERIFYHOST  => 0,
        CURLOPT_SSLCERT     => __DIR__ . "/nko.cer",
        CURLOPT_SSLKEY      => __DIR__ . "/nko.key",
    ];

    var_dump("{$uri}{$get}");

    $result  = $browser->request("{$uri}{$get}", "GET", $headerRows, "", true, $curlRows);
    file_put_contents($file, $result);
    var_dump($result, $browser->resHeader);

} catch (Exception $e) {
    var_dump($e);
    $logs->add($e);
}