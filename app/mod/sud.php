<?php

require_once(MOD_DIR . "main.php");

require_once(INC_DIR . "SudokuSolver.class.php");

set_time_limit(600);

class sud extends main
{

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
        ];

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