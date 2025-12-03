<?php

/**
 * @author <first@mail.ru>
 * @since  2018-03-30
 */

use app\inc\Logs;

require_once("../app/cnf/main.php");
require_once(INC_DIR . "Dump.class.php");

$uri = explode("?", $_SERVER["REQUEST_URI"]);
$httpRequest = explode("/", substr($uri[0], 1));

if ("ajax" == $httpRequest[0]) {
    require_once (APP_DIR . "ajax.php");
    exit;
}

try {
    $moduleClass = "main";
    $moduleMethod = "show";
    if (! empty($httpRequest[0]) and is_file(MOD_DIR . $httpRequest[0] . ".php")) {
        $moduleClass = array_shift($httpRequest);
    }
    require_once (MOD_DIR . $moduleClass . ".php");
    $moduleObj = new $moduleClass();
    if (!empty($httpRequest[0]) and method_exists($moduleObj, $httpRequest[0])) {
        $moduleMethod = array_shift($httpRequest);
    }
    $moduleObj->$moduleMethod($httpRequest);
    $moduleTpl = $moduleClass . DS . $moduleMethod . ".tpl";
    if (!is_file(TPL_DIR . $moduleTpl)) {
        $moduleTpl = $moduleClass . ".tpl";
    }
    $moduleObj->display($moduleTpl);
} catch (Exception $exception) {
    header("Content-Type: text/html; charset=utf-8");
    $code = $exception->getCode();
    if (is_numeric($code)) {
        header("HTTP/1.1 " . $code);
    } else {
        header("HTTP/1.1 404");
    }
    _dump(strval($exception), $code);
    if (404 != $code) {
        $logs = new Logs("public");
        $logs->add($exception);
    }
}