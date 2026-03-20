<?php
use app\inc\Lock;
use app\inc\Logs;
use app\inc\Solver;
use app\inc\SudokuSolver;
use app\inc\TelegramClass;
use app\inc\WaterSolver;
use app\inc\ProxyClass;

/**
 * @category    robot
 * @package     proxy
 * @author      <andrey.a.sirotkin@megafon.ru>
 * @since       2026-03-17
 */

set_time_limit(0);
ini_set("memory_limit", -1);

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "ProxyClass.php");

error_reporting(E_ALL);

$proxy  = new ProxyClass();
$proxy->import();

