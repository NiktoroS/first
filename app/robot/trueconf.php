<?php
use app\inc\Lock;
use app\inc\Logs;
use app\inc\TrueconfClass;

/**
 * @category    robot
 * @package     telegram
 * @author      <andrey.a.sirotkin@megafon.ru>
 * @since       2025-11-14
 */

set_time_limit(0);
ini_set("memory_limit", -1);

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "Lock.php");
require_once(INC_DIR . "Logs.php");
require_once(INC_DIR . "TrueconfClass.php");

error_reporting(E_ALL);

$lock = new Lock();
$logs = new Logs();

if (false === $lock->setLock()) {
    $logs->add("error in set lock");
    exit;
}

try {
    $trueconf   = new TrueconfClass();
//    $trueconf->server();
    $trueconf->authorization();
    var_dump($trueconf->access_token);
//    var_dump($trueconf->request("api/v3.0/chats?types=personal&access_token={$trueconf->access_token}"));
    var_dump($trueconf->requestGet("chats"));
    exit;

    var_dump($trueconf->requestGet("logs/events"));
//exit;
    var_dump($trueconf->requestGet("aliases"));
    var_dump($trueconf->requestGet("groups"));
//    var_dump($trueconf->requestGet("roadcast/presets"));

//    var_dump($trueconf->requestGet("users/andrey.a.sirotkin"));
//    var_dump($trueconf->requestGet("software/clients"));
    var_dump($trueconf->requestGet("users/andrey.a.sirotkin/addressbook"));

    /*
    $post = [
        "topic" => "My Conference",
        "type" => 0,
        "auto_invite" => 1,
        "max_participants" => 5,
        "invitations" => [
            [
                "id" => "andrey.a.sirotkin"
            ],
        ],
        "schedule" => [
            "type" => -1
        ],
        "owner" => "andrey.a.sirotkin"
    ];
    var_dump($trueconf->requestPost("conferences", $post));

    $post = [
        "type"  => 1,
        "id"    => 1,
        "method"    => "createP2PChat",
        "payload"   => [
            "userId" => "andrey.a.sirotkin"
        ]
    ];
    var_dump($trueconf->requestPost("createP2PChat", $post));
*/
} catch (Exception $exception) {
    echo($exception->getMessage());
    $logs->add($exception);
}

$lock->delLock();