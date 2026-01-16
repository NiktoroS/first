<?php
use app\inc\DeepState;
use app\inc\Lock;
use app\inc\Logs;

/**
 * @category robot
 * @package  xe.com
 * @author   <mail@mail.ru>
 * @since    25.09.2025
 */

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "Logs.php");
require_once(INC_DIR . "Lock.php");
require_once(INC_DIR . "DeepState.php");

error_reporting(E_ALL);

$logs = new Logs();
$lock = new Lock();

if (false === $lock->setLock()) {
    $logs->add("error in set lock");
    exit;
}

//$file = $_SERVER["argv"][1] ?? (ROOT_DIR . "userdata" . DS . "ds20251005.html");

$deepState = new DeepState();
$deepState->updateAttrRows();
//$deepState->saveFile($file);

$lock->delLock();
