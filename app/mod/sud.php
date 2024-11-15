<?php

use app\inc\Logs;
use app\inc\SudokuSolver;
use app\inc\TelegramClass;

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

    public function show($params = [])
    {
        $this->data = [
            "gadgetRows" => [
                "Mi11" => "Mi11 lite",
                "Pad6" => "Pad 6"
            ]
        ];
    }

    public function solve($params = [])
    {
        SudokuSolver::getRowsFromFile($params);
        $startTime  = microtime(true);
        $resultRows = SudokuSolver::solve($params["rows"]);
        $result = [
            "rows"  => $resultRows,
            "time"  => microtime(true) - $startTime,
            "i"     => SudokuSolver::$i,
            "acc"   => SudokuSolver::saveAcc($resultRows, $params["rows"], $params["level"], $params["gadget"])
        ];
        $log = new Logs("sud");
        try {
            $telegram = new TelegramClass();
            $response = $telegram->sendDocument(
                ROOT_DIR . "content" . DS . "{$result["acc"]}.txt"
            );
            $log->add(var_export($response, true));
        } catch (Exception $e) {
            $log->add($e->getMessage());
        }
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