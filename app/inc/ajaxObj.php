<?php
use app\inc\Logs;

/**
 * @category обработчик ajax-запросов к методам модулей
 * @author <first@mail.ru>
 * @since  2018-03-30
 */

set_time_limit(600);

if (isset($_GET["module"]) and isset($_GET["method"])) {
    $params = $_GET;
} else if (isset($_POST["module"]) and isset($_POST["method"])) {
    $params = $_POST;
} else {
    showError("недостаточно обязательных параметров");
}

$module = $params["module"];
$method = $params["method"];

$fileModule = MOD_DIR . $module . ".php";
if (!is_file($fileModule)) {
    showError("несущесвующий файл: " . $fileModule);
}

require_once $fileModule;
if (!class_exists($module)) {
    showError("неизвестный модуль: " . $module);
}


try {
    header("Content-Type: text/html; charset=utf-8");
    $moduleObj = new $module();

    if (!method_exists($moduleObj, $method)) {
        showError("несуществующий метод: " . $method . " модуля: " . $module);
    }
    unset($params["module"], $params["method"]);
    if (isset ($params["objAjaxTime"])) {
        unset($params["objAjaxTime"]);
    }
    if (empty($params["X-CSRF-Token"])) {
        $logs = new Logs("ajaxWoToken");
        $logs->add(json_encode($_SERVER));
        $logs->add(json_encode($params));
    } else {
        if (
            isset($_SESSION['X-CSRF-Token']) &&
            isset($params["X-CSRF-Token"]) &&
            $_SESSION["X-CSRF-Token"] != $params["X-CSRF-Token"]
        ) {
            $logs = new Logs("wrongToken");
            $logs->add(json_encode($_SERVER));
            $logs->add(json_encode($params));
        }
        unset($params["X-CSRF-Token"]);
    }
    $moduleObj->$method($params);
    $moduleObj->display($module . DS . $method . ".tpl");
} catch (Exception $e) {
    if ($e->getCode()) {
        showError($e->getMessage(), "userError", $e->getCode());
    } else {
        showError(strval($e));
    }
}

function showError($error, $type = "systemError", $code = 500)
{
    $logs = new Logs("ajax.php");
    $logs->add("[{$type}] " . (isset($params) ? "params: " . var_export($params, true) : "") . strval($error));
    header("HTTP/1.1 {$code}");
    exit("<input type='hidden' id='{$type}' name='{$code}' value='" . strval($error) . "'/>");
}
