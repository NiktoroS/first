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

    static public function saveAcc($resultRows, $startRows)
    {
        $stamp = time();
        $data = [
            'targets' => [],
            'antiDetection' => true,
            'id' => 0,
            'name' => "Config {$stamp}",
            'numberOfCycles' => 1,
            'stopConditionChecked' => 2,
            'timeValue' => 400
        ];
        for ($button = 1; $button < 10; $button ++) {
            $data['targets'][] = self::getAccButton($button);
            foreach ($resultRows as $y => $row) {
                foreach ($row as $x => $val) {
                    if ($val == $button && empty($startRows[$y][$x])) {
                        $data['targets'][] = self::getAccField($x, $y);
                    }
                }
            }
        }
        file_put_contents(
            ROOT_DIR . "content" . DS . "config.{$stamp}.txt",
            json_encode([$data])
        );
        return $stamp;
    }

    static public function saveAccs($rows, $startRows)
    {
        $fileTemplate = ROOT_DIR . "content" . DS . "Rule.json";
        $array  = json_decode(file_get_contents($fileTemplate), true);
        $configRows = [];
        for ($button = 1; $button <= 1; $button ++) {
            $configRows[] = self::getButton($button);
            foreach ($rows as $y => $row) {
                foreach ($row as $x => $val) {
                    if ($val == $button && empty($startRows[$y][$x])) {
                        $configRows[] = self::getField($x, $y);
                    }
                }
            }
        }
        $array["configList"][0]["config"] = json_encode($configRows);

        $stamp = microtime(true);
        $array["name"] = "Rule{$stamp}";
        $fileOut = ROOT_DIR . "content" . DS . "Rule{$stamp}.accs";
        file_put_contents($fileOut, json_encode($array));
    }

    static private function getAccButton($val)
    {
        $val --;
        if ($val < 0) {
            $val = 9;
        }
        return [
            "delayUnit"  => 0,
            "delayValue" => 80,
            "duration"   => 0,
            "type"  => 0,
            "xPos"  => ($val % 5) * 200 + 60,
            "xPos1" => -1,
            "yPos"  => floor($val / 5) * 475 + 1525,
            "yPos1" => -1
        ];
    }

    static private function getAccField($x, $y)
    {
        return [
            "delayUnit"  => 0,
            "delayValue" => 80,
            "duration"   => 0,
            "type"  => 0,
            "xPos"  => $x * 120 + 50,
            "xPos1" => -1,
            "yPos"  => $y * 128 + 360,
            "yPos1" => -1
        ];
    }

    static private function getButton($val)
    {
        if ("c" == $val) {
            $val = 10;
        }
        $val --;
        return [
            "b" => -1,
            "count" => 1,
            "delay" => 5,
            "duration" => 5,
            "point" => [
                "count" => 1,
                "delay" => 5,
                "duration" => 5,
                "gravity" => 0,
                "index" => 0,
                "randomDistance" => 5,
                "x" => ($val % 5) * 180 + 40,
                "y" => floor($val / 5) * 350 + 1500
            ],
            "randomDelay" => 0,
            "randomDistance" => 0,
            "randomDuration" => 0,
            "type" => 0
        ];
    }

    static private function getField($x, $y)
    {
        return [
            "b" => -1,
            "count" => 1,
            "delay" => 0,
            "duration" => 0,
            "point" => [
                "count" => 1,
                "delay" => 0,
                "duration" => 0,
                "gravity" => 0,
                "index" => 0,
                "randomDistance" => 5,
                "x" => $x * 120 - 30,
                "y" => $y * 115 + 345
            ],
            "randomDelay" => 0,
            "randomDistance" => 0,
            "randomDuration" => 0,
            "type" => 0
        ];
    }
}