<?php
use app\inc\BrowserClass;
use app\inc\Logs;

/**
 * @category
 * @package  paymgate
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    02.03.2020
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
    $uri = "http://localhost:850";
    $get = "transport-card-1.php?paymid=21534935&request_id=e08d2759fa9658a1c08790df543c30f0&ext_id=0594351034&account=9643109066600000508&recv_code=16069&user_id=8001&sum=0.00&paymdate=20210303124041&param0=9643109066600000508&param1=value%23100%234367620%2320000&param2=&param3=&param4=&param5=&param6=&param7=&param8=&param9=&subscr_id=71&bill_reg_id=&termtype=007-20&resp_param0=&resp_param1=&resp_param2=&resp_param3=&resp_param4=&payer901=&payer902=&payer903=&payer904=&payer905=&payer906=&payer907=&payer908=&payer909=&payer910=&payer911=&payer920=&payer921=&payer922=&payer922=&payer923=&payer924=";

    $browser = new BrowserClass();

    $result  = $browser->request("{$uri}/{$get}", "GET");
    var_dump($result);
} catch (Exception $e) {
    var_dump($e);
    $logs->add($e);
}