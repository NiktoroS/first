<?php

use app\inc\Logs;
use app\inc\WaterSolver;

/**
 * @category robot
 * @package  ws solve
 * @author   <first@mail.ru>
 * @since    2023-12-11
 */

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "Logs.php");
require_once(INC_DIR . "WaterSolver.php");

ini_set("memory_limit", -1);
error_reporting(E_ALL);
set_time_limit(0);

$logs = new Logs();

try {
    $ws = new WaterSolver();
    $result = $ws->solveStep($_SERVER["argv"][1]);
    echo (json_encode($result));
} catch (Exception $exception) {
    $logs->add(var_export($exception, true));
    var_dump($exception);
}

