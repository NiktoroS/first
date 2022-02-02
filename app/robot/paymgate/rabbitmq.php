<?php
/**
 *
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__, 2) . "/cnf/main.php");
require_once(INC_DIR . "Logs.class.php");
require_once(INC_DIR . "Lock.class.php");


$settings["rabbitmq"] = [
    "host"  => "ti-queues-01.int.paymgate.ru",
    "port"  => 5672,
    "query" => "paymgate_transactions",
    "user"  => "guest",
    "pass"  => "guest",
];

$json   = '{"id":"123e4567-e89b-12d3-a456-426655440000" "recv_code": 6010, "user_id":8004, "request_time": 100, "result": 1, "type_request": "check", "date": "2021-02-28", "datetime":"2021-02-28 23:59:59.1234", "balance":100000.00}';

$connection = new \PhpAmqpLib\Connection\AMQPStreamConnection($settings["rabbitmq"]["host"], $settings["rabbitmq"]["port"], $settings["rabbitmq"]["user"], $settings["rabbitmq"]["pass"]);
$channel = $connection->channel();

$msg = new \PhpAmqpLib\Message\AMQPMessage($json);
$channel->basic_publish($msg, "", $settings["rabbitmq"]["query"]);

echo " [x] Sent '{$json}'\n";

$channel->close();
$connection->close();


function GUID()
{
    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    }
    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}