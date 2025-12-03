<?php
namespace app\inc;

require_once(INC_DIR . "SudokuSolver.php");
require_once(INC_DIR . "WaterSolver.php");

/**
 *
 * @author andrey.a.sirotkin@megafon.ru
 */
class Solver
{
    public static function getSolverFromFile($tmpFile = "")
    {
        $level = WaterSolver::getLevelFromFile($tmpFile);
        if ($level) {
            return [
                "cntColors" => 15,
                "level" => $level,
                "type"  => "Water"
            ];
        }
        $level = SudokuSolver::getLevelFromFile($tmpFile);
        return [
            "level" => $level,
            "type"  => "Sudoku"
        ];
    }
}