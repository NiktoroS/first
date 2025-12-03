<?php

/**
 *
 */

use app\inc\Logs;

set_time_limit(0);

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "Logs.php");

error_reporting(E_ALL);

$logs = new Logs();


try {
    $fileTemplate = ROOT_DIR . "content" . DS . "config0.txt";
    $array  = json_decode(file_get_contents($fileTemplate), true);
    print_r($array);
    exit;

    $fileTemplate = ROOT_DIR . "content" . DS . "Rule.json";
    $array  = json_decode(file_get_contents($fileTemplate), true);
    print_r($array);
    $config = json_decode($array["configList"][0]["config"], true);
    print_r($config);
    exit;

    $configRows = [];
    for ($button = 9; $button >= 1; $button --) {
        $configRows[] = getButton($button);
    }
    $array["configList"][0]["config"] = json_encode($configRows);
    $fileOut = ROOT_DIR . "content" . DS . "Rule.accs";
    file_put_contents($fileOut, json_encode($array));
} catch (Exception $e) {
    var_dump($e);
    $logs->add($e);
}


function getButton($val)
{
    if ("c" == $val) {
        $val = 10;
    }
    $val --;
    return [
        "b" => -1,
        "count" => 1,
        "delay" => 0,
        "duration" => 0,
        "point" => [
            "count" => 1,
            "delay" => 0,
            "duration" => 0,
            "gravity" => 0,
            "index" => 0,
            "randomDistance" => 5,
            "x" => ($val % 5) * 180 + 40,
            "y" => floor($val / 5) * 350 + 1500
        ],
        "randomDelay" => 0,
        "randomDistance" => 0,
        "randomDuration" => 0,
        "type" => 0
    ];
}
