<?php
namespace app\inc;

/**
 *
 * @author a.sirotkin@auvix.ru
 * @author andrey.a.sirotkin@megafon.ru
 */
class SudokuSolver
{
    static public $i = 0;

    static public function getRowsFromFile(&$params = [])
    {
        if (!$_FILES) {
            return;
        }
        if ("image/jpeg" == $_FILES["file"]["type"]) {
            $im = imagecreatefromjpeg($_FILES["file"]["tmp_name"]);
        } else {
            $im = imagecreatefrompng($_FILES["file"]["tmp_name"]);
        }
        $params["rows"] = self::getRowsFromIm($im);
    }

    static public function getRowsFromIm($im)
    {
        $rows = [];
        for ($row = 0; $row < 9; $row ++) {
            for ($col = 0; $col < 9; $col ++) {
                $errors = [];
                for ($val = 0; $val <= 9; $val ++) {
                    $errors[$val] = 0;
                    $imCheck = imagecreatefrompng(APP_DIR . "resources/sud/{$val}.png");
                    $rgbRows = [];
                    for ($x = 0; $x < 56; $x ++) {
                        for ($y = 0; $y < 56; $y ++) {
                            $rgb = imagecolorat($im, $x + 6 + ($col * 64), $y + 184 + ($row * 64));
                            $r = ($rgb >> 16) & 0xFF;
                            $g = ($rgb >> 8) & 0xFF;
                            $b = $rgb & 0xFF;
                            $rgbCheck = imagecolorat($imCheck, $x, $y);
                            if (empty($rgbRows[$rgbCheck])) {
                                $rgbRows[$rgbCheck] = 0;
                            }
                            $rgbRows[$rgbCheck] ++;
                            if ($r < 128 && $g < 128 && $b < 128) {
                                if (!imagecolorat($imCheck, $x, $y)) {
                                    $errors[$val] ++;
                                }
                            } else {
                                if (imagecolorat($imCheck, $x, $y)) {
                                    $errors[$val] ++;
                                }
                            }
                        }
                    }
                }
                asort($errors);
                $keys = array_keys($errors);
                $rows[$row][$col] = $keys[0];
            }
        }
        return $rows;
    }

    static public function saveAcc($resultRows, $startRows, $level = null, $gadget = "Mi11")
    {
        if (!$level) {
            $level = time();
            $return = "{$gadget}.{$level}";
        } else {
            $level = sprintf("%05d", $level);
            $return = "{$gadget}.{$level}." . time();
        }
        $data = [
            'targets' => [],
            'antiDetection' => true,
            'id' => 0,
            'name' => "Config {$gadget} {$level}",
            'numberOfCycles' => 1,
            'stopConditionChecked' => 2,
            'timeValue' => 400
        ];
        for ($button = 1; $button < 10; $button ++) {
            $data['targets'][] = self::getAccButton($button, $gadget);
            foreach ($resultRows as $y => $row) {
                foreach ($row as $x => $val) {
                    if ($val == $button && empty($startRows[$y][$x])) {
                        $data['targets'][] = self::getAccField($x, $y, $gadget);
                    }
                }
            }
        }
        file_put_contents(
            ROOT_DIR . "content" . DS . "{$return}.txt",
            json_encode([$data])
        );
        return $return;
    }

    static public function solve($startSolution)
    {
        $solution = $startSolution;
        if (self::solveHelper($solution)) {
            return $solution;
        }
        return [];
    }

    static private function findPossibleValues($rowIndex, $columnIndex, $puzzle)
    {
        $values = range(1, 9);
        $values = array_diff($values, self::getRowValues($rowIndex, $puzzle), self::getColumnValues($columnIndex, $puzzle), self::getBlockValues($rowIndex, $columnIndex, $puzzle));
        return $values;
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

    static private function getBlockValues($rowIndex, $columnIndex, $puzzle)
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

    static private function getColumnValues($columnIndex, $puzzle)
    {
        $values = [];
        for ($r = 0; $r < 9; ++$r) {
            $values[] = $puzzle[$r][$columnIndex];
        }
        return $values;
    }

    static private function getRowValues($rowIndex, $puzzle)
    {
        return $puzzle[$rowIndex];
    }

    static private function solveHelper(&$solution)
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

}