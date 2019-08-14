<?php
use app\inc\SudokuSolver;

require_once(MOD_DIR . "main.php");

require_once(INC_DIR . "SudokuSolver.class.php");

set_time_limit(60);

class sud extends main
{

    public function solve($params = [])
    {
        $rows = [];
        $row    = explode(",", $params["rows"]);
        foreach ($row as $key => $val) {
            $rows[$key / 9][$key % 9] = intval($val);
        }
        header("Content-type: application/json");

        $startTime  = microtime(true);
        $resultRows = SudokuSolver::solve($rows);
        $result = [
            "rows"  => $resultRows,
            "time"  => microtime(true) - $startTime,
        ];
        echo (json_encode($result));
        exit;
    }
}