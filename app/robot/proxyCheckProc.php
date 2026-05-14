<?php
use app\inc\ProxyClass;
use app\inc\BrowserClass;
use app\inc\Logs;

/**
 * @category    robot
 * @package     proxy
 * @author      <andrey.a.sirotkin@megafon.ru>
 * @since       2026-03-17
 */

set_time_limit(0);
ini_set("memory_limit", -1);

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "BrowserClass.php");
require_once(INC_DIR . "Logs.php");
require_once(INC_DIR . "ProxyClass.php");

error_reporting(E_ALL);

$browser    = new BrowserClass();
$logs       = new Logs();
$proxyObj   = new ProxyClass();

$browser->timeOut = 5;
$att = 0;
while (1 == 1) {
    if (getenv("PROXY") || getenv("EXTERNAL_IP") != gethostbyname(gethostname()) ) {
        break;
    }
    $proxyRow = $proxyObj->getProxy($_SERVER["argv"][1] ?? 1, $_SERVER["argv"][2] ?? 0, true);
    $browser->proxy = base64_decode($proxyRow["name"]);
    $browser->proxyAuth = $proxyRow["auth"] ?? null;
    $microtime = microtime(true);
    $str = date("[Y-m-d H:i:s]") . " {$browser->proxy} ";
    try {
        $browser->request("https://accounts.google.com/RotateCookies");
        $str .= "time=" . round(microtime(true) - $microtime, 2);
        $logs->add($str);
        $proxyObj->saveSuccess($proxyRow, microtime(true) - $microtime);
    } catch (\Exception $exception) {
        $str .= $exception->getMessage() . " [" . $exception->getCode() . "] time=" . round(microtime(true) - $microtime, 2);
        $logs->add($str);
        $att ++;
        if (100 == $att) {
            $att = 0;
            if (mb_strlen(file_get_contents("https://megafon.ru")) < 100000) {
                sleep(5);
                if (mb_strlen(file_get_contents("https://megafon.ru")) < 100000) {
                    die("Похоже нету подключения к интернету");
                }
            }
        }
        $proxyRow["error_code"] = $exception->getCode();
        $proxyObj->saveFail($proxyRow);
    }
}