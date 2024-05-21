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

    static public function saveAcc($resultRows, $startRows, &$params = [])
    {

        $params["level"]  = empty($params["level"]) ? time() : sprintf("%04d", $params["level"]);
        $params["gadget"] = empty($params["gadget"]) ? "Mi11" : $params["gadget"];
        $data = [
            'targets' => [],
            'antiDetection' => true,
            'id' => 0,
            'name' => "Config {$params["gadget"]} {$params["level"]}",
            'numberOfCycles' => 1,
            'stopConditionChecked' => 2,
            'timeValue' => 400
        ];
        for ($button = 1; $button < 10; $button ++) {
            $data['targets'][] = self::getAccButton($button, $params["gadget"]);
            foreach ($resultRows as $y => $row) {
                foreach ($row as $x => $val) {
                    if ($val == $button && empty($startRows[$y][$x])) {
                        $data['targets'][] = self::getAccField($x, $y, $params["gadget"]);
                    }
                }
            }
        }
        file_put_contents(
            ROOT_DIR . "content" . DS . "{$params["gadget"]}.{$params["level"]}.txt",
            json_encode([$data])
        );
        return "{$params["gadget"]}.{$params["level"]}";
    }

    static private function getAccButton($val, $gadget)
    {
        $val --;
        if ($val < 0) {
            $val = 9;
        }
        return [
            "delayUnit"  => 0,
            "delayValue" => "Pad6" == $gadget ? 25: 250,
            "duration"   => 0,
            "type"  => 0,
            "xPos"  => "Pad6" == $gadget ? ($val % 5) * 320 + 110 : ($val % 5) * 200 + 60,
            "xPos1" => -1,
            "yPos"  => "Pad6" == $gadget ? floor($val / 5) * 360 + 2180 : floor($val / 5) * 475 + 1525,
            "yPos1" => -1
        ];
    }

    static private function getAccField($x, $y, $gadget)
    {
        return [
            "delayUnit"  => 0,
            "delayValue" => "Pad6" == $gadget ? 25: 250,
            "duration"   => 0,
            "type"  => 0,
            "xPos"  => "Pad6" == $gadget ? 100 + $x * 200 : 50 + $x * 120,
            "xPos1" => -1,
            "yPos"  => "Pad6" == $gadget ? 360 + $y * 205 : 370 + $y * 125,
            "yPos1" => -1
        ];
    }

}