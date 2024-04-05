<?php

use app\inc\SudokuSolver;
//use app\inc\TelegramClass;
use app\inc\Logs;
use Telegram\Bot\Api;

require_once(MOD_DIR . "main.php");
require_once(INC_DIR . "SudokuSolver.php");
require_once(INC_DIR . "TelegramClass.php");

set_time_limit(600);

class sud extends main
{

    public function download()
    {
        $content = file_get_contents(ROOT_DIR . "content" . DS . "config.{$_GET['stamp']}.txt");
        header('Content-Description: File Transfer');
        header("Content-Type: application/json");
        header('Content-Disposition: attachment; filename="config.' . $_GET['stamp'] .'.txt"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . strlen($content));
        echo($content);
        exit;
    }

    public function solve($params = [])
    {
        $rows = [];
        $row    = explode(",", $params["rows"]);
        foreach ($row as $key => $val) {
            $rows[$key / 9][$key % 9] = intval($val);
        }

        $startTime  = microtime(true);
        $resultRows = SudokuSolver::solve($rows);
        $result = [
            "rows"  => $resultRows,
            "time"  => microtime(true) - $startTime,
            "i"     => SudokuSolver::$i,
            "acc"   => SudokuSolver::saveAcc($resultRows, $rows)
        ];

        $log = new Logs("sud");
        $telegram = new Api("329014600:AAGB3v56moIsLum3gsfiNsE6-9u4WKGqOrg");
        $response = $telegram->sendDocument([
            'chat_id' => '205579980',
            'document' => ROOT_DIR . "content" . DS . "config.{$result['acc']}.txt",
            'caption' => "config.{$result['acc']}.txt"
        ]);
        $messageId = $response->getMessageId();
        $log->add(var_export($messageId, true));

        header("Content-type: application/json");
        echo (json_encode($result));
        exit;
    }

    public function array_diff($params = [])
    {
        $aRow   = explode(",", $params["a"]);
        $bRow   = explode(",", $params["b"]);
        echo (json_encode(["c" => join(", ", array_diff($aRow, $bRow))]));
        exit;
    }
}