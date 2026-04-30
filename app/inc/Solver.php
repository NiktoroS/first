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
    /**
     *
     * @param string $tmpFile
     * @param string $type
     * @return []
     */
    public static function getSolverFromFile(string $tmpFile, string $type)
    {
        $level = WaterSolver::getLevelFromFile($tmpFile, $type);
        if ($level) {
            return [
                "cntColors" => 15,
                "level" => $level,
                "type"  => "Water"
            ];
        }
        $level = SudokuSolver::getLevelFromFile($tmpFile, $type);
        return [
            "level" => $level,
            "type"  => "Sudoku"
        ];
    }
}