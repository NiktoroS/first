<?php
/**
 * @category pornstars
 * @package  xv
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    28.02.2017
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__, 2) . "/cnf/main.php");
require_once(INC_DIR . "Xv.class.php");
require_once(INC_DIR . "Lock.class.php");

error_reporting(E_ALL);

$copies = 1;

$lock = new Lock();
if (false === ($copy = $lock->setLock($copies))) {
    exit;
}

$logs = new Logs();
$logs->setCopy($copy);

try {
    $logs->add("start");
    $xvObj = new xvClass();
    $hrefRows = [
        "/channels-new",
        "/channels",
        "/pornstars",
        "/profileslist",
        "/profiles-index",
        "/porn/russian",
        "/porn/ukrainian",
        "/porn/eesti",
        "/porn/latviesu",
        "/porn/lietuviu",
    ];
    foreach ($hrefRows as $href) {
        $logs->add($xvObj->site . $href . " done : " . $xvObj->parceHref($href));
    }
} catch (Exception $e) {
    $logs->add($e);
}
