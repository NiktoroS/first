<?php
namespace app\inc;

/**
 *
 * @author a.sirotkin@auvix.ru
 */
class SudokuSolver
{
    static public $i = 0;

    static function solve($puzzle)
    {
        $solution = $puzzle;
        if (self::solveHelper($solution)) {
            return $solution;
        }
        return [];
    }

    static function solveHelper(&$solution)
    {
        self::$i ++;
//        $logs = new Logs("sud");
//        $logs->add(self::$i . " :: " . var_export($solution, true));

        $minRow     = -1;
        $minColumn  = -1;

        $minValues  = [];

        while (true) {
            $minRow = -1;
            for ($rowIndex = 0; $rowIndex < 9; $rowIndex ++) {
                for ($columnIndex = 0; $columnIndex < 9; $columnIndex ++) {
                    if ($solution[$rowIndex][$columnIndex] != 0) {
                        continue;
                    }
                    $possibleValues = self::findPossibleValues($rowIndex, $columnIndex, $solution);
                    $possibleVaueCount = count($possibleValues);
                    if ($possibleVaueCount == 0) {
                        return false;
                    }
                    if ($possibleVaueCount == 1) {
                        $solution[$rowIndex][$columnIndex] = array_pop($possibleValues);
                    }
                    if ($minRow < 0 || $possibleVaueCount < count($minValues)) {
                        $minRow     = $rowIndex;
                        $minColumn  = $columnIndex;

                        $minValues  = $possibleValues;
                    }
                }
            }
            if ($minRow == -1) {
                return true;
            } elseif (count($minValues) > 1) {
                break;
            }
        }

        foreach ($minValues as $v) {
            $solutionCopy = $solution;
            $solutionCopy[$minRow][$minColumn] = $v;
            if (self::solveHelper($solutionCopy)) {
                $solution = $solutionCopy;
                return true;
            }
        }
        return false;
    }

    static function findPossibleValues($rowIndex, $columnIndex, $puzzle)
    {
        $values = range(1, 9);

        $values = array_diff($values, self::getRowValues($rowIndex, $puzzle), self::getColumnValues($columnIndex, $puzzle), self::getBlockValues($rowIndex, $columnIndex, $puzzle));

        return $values;
    }

    static function getRowValues($rowIndex, $puzzle)
    {
        return $puzzle[$rowIndex];
    }

    static function getColumnValues($columnIndex, $puzzle)
    {
        $values = [];
        for ($r = 0; $r < 9; ++$r) {
            $values[] = $puzzle[$r][$columnIndex];
        }
        return $values;
    }

    static function getBlockValues($rowIndex, $columnIndex, $puzzle)
    {
        $values = [];
        $blockRowStart      = 3 * floor($rowIndex / 3);
        $blockColumnStart   = 3 * floor($columnIndex / 3);
        for ($r = 0; $r < 3; ++$r) {
            for ($c = 0; $c < 3; ++$c) {
                $values[] = $puzzle[$blockRowStart + $r][$blockColumnStart + $c];
            }
        }
        return $values;
    }
}