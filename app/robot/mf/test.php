<?php
use app\inc\BrowserClass;
use app\inc\Logs;

/**
 * @category
 * @package  xv
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    02.03.2021
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__, 2) . "/cnf/main.php");
require_once(INC_DIR . "Logs.php");
require_once(INC_DIR . "BrowserClass.php");

error_reporting(E_ALL);

$logs = new Logs();

try {
    $browser = new BrowserClass();
    $url = "https://cargo.megafon.ru/api/address/GetAddresses?itn=&kpp1c=&date_start=10.06.2026&date_end=11.06.2026";
    $url = "https://cargo.megafon.ru/admin/get_log";
    $url = "http://localhost:8000/admin/get_log";
//    $url = "http://localhost:8000/api/address/GetAddresses?itn=&kpp1c=&date_start=10.06.2026&date_end=11.06.2026";
//    $url = "https://cargo.test-middle.megafon.ru:2047/admin/get_log";
//     if (getenv("PROXY")) {
//         $browser->proxy = getenv("PROXY");
//         if (getenv("PROXY_AUTH")) {
//             $browser->proxyAuth = getenv("PROXY_AUTH");
//         }
//     }
//    $auth = base64_encode("andrey.a.sirotkin@megafon.ru:10Oct30May1975!1");
    $auth = base64_encode("andrey.a.sirotkin@megafon.ru:cargo");
    $res = $browser->request(
        $url,
        "GET",
        [
            "Authorization" => "Basic {$auth}",
            "Accept"        => "application/json"
        ]
    );
    var_dump($res);
} catch (Exception $e) {
    var_dump($e);
    $logs->add($e);
}
