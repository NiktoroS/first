<?php
/**
 * @category robot
 * @package  wooordhunt
 * @author   <first@mail.ru>
 * @since    2020-02-26
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "Logs.class.php");
require_once(INC_DIR . "Lock.class.php");
require_once(INC_DIR . "Telegram.class.php");
require_once(INC_DIR . "Storage/MySqlStorage.php");

error_reporting(E_ALL);

$logs = new Logs();
$lock = new Lock();

if (false === $lock->setLock()) {
    $logs->add("error in set lock");
    exit;
}

global $dsnMySql;

$dsnMySql["name"] = "sirotkin";


try {
    $mySql      = new MySqlStorage($dsnMySql);
    $telegram   = new Telegram();

    $browser    = new Browser();//"socks5://127.0.0.1:9050");
    $url        = "http://4flaga.ru/d_1001_2000.html";
    $url        = "http://4flaga.ru/d_1_1000.html";

    $htmlObj    = $browser->requestHtml($url);
    if (!$htmlObj) {
        throw new Exception("not get: {$url}");
    }
    $pObj = $htmlObj->find("p.newsgr", 0);
    if (!$pObj) {
        throw new Exception("not found: p.newsgr");
    }
    $rows = [];
    foreach (explode("<br>", $pObj->innerHtml) as $string) {
        $string = preg_replace(['/\n/', '/&#13;/'], [' ', ''], $string);
        var_dump($string);

        $matches = [];
        if (!preg_match('/\<a\s+href\=\"http\:\/\/4flaga\.ru\/sounds\/\w+\.mp3\"\>\s+(\w+)\<\/a\>\s+\-\s+(.+)/', $string, $matches)) {
            continue;
        }
        $row = [
            "language" => "en",
            "lenght"   => mb_strlen($matches[1]),
            "word"     => $matches[1],
            "description" => trim($matches[2]),
        ];
        $rows[] = $row;
    }
    var_dump($rows);
    $mySql->insertOrUpdateRows("dictionary", $rows);

} catch (Exception $e) {
    echo($e->getMessage());
    $logs->add($e);
}

$lock->delLock();
