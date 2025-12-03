<?php
namespace app\inc;

require_once(INC_DIR . "Gd.php");

/**
 *
 * @author a.sirotkin@auvix.ru
 * @author andrey.a.sirotkin@megafon.ru
 */
class SudokuSolver
{
    static public $i = 0;

    /**
     *
     * @param string $tmpFile
     * @return number
     */
    static public function getLevelFromFile(string $tmpFile = "")
    {
        $image    = imagecreatefromjpeg($tmpFile);
        $width    = imagesx($image);
        if (576 == $width) {
            $xFirst   = 82;
            $xLast    = 180;
            $yFrom    = 79;
            $yTo      = 101;
        } elseif (582 == $width) {
            $xFirst   = 88;
            $xLast    = 194;
            $yFrom    = 83;
            $yTo      = 106;
        }
        $isNegative = Gd::isNegative($image, $xFirst, $yFrom, 420);
        $numbers  = [];
        do {
            $xFirst     = self::getFirstX($image, $xFirst, $xLast, $yFrom, $yTo, $isNegative);
            $numbers[]  = self::getNumber($image, $xFirst, $yFrom, $isNegative);
        } while($xFirst < $xLast && count($numbers) < 5);
        return intval(join("", $numbers));
    }

    /**
     *
     * @param \GdImage $image
     * @param int $xFrom
     * @param int $xTo
     * @param int $yFrom
     * @param int $yTo
     * @param bool $isNegative
     * @return int
     */
    static private function getFirstX($image, int $xFrom, int $xTo, int $yFrom, int $yTo, bool $isNegative)
    {
        for ($x = $xFrom; $x < $xTo; $x ++) {
            for ($y = $yFrom; $y < $yTo; $y ++) {
                if (!Gd::isWhite($image, $x, $y, $isNegative)) {
                    return $x;
                }
            }
        }
        return $xFrom;
    }

    /**
     *
     * @param \GdImage $image
     * @param int $xFrom
     * @param int $yFrom
     * @param bool $isNegative
     * @return number
     */
    static private function getNumber($image, int &$xFrom, int $yFrom, bool $isNegative)
    {
        $dir = APP_DIR . "resources" . DS . "sud" . DS . "level";
        $errors = [];
        $xMaxs  = [];
        foreach (scandir($dir) as $file) {
            $output = [];
            if (!preg_match('/(\d\.\d+)\.png$/', $file, $output)) {
                continue;
            }
            $number = $output[1];
            $errors[$number] = 0;
            $cnt  = 0;
            $fileFull = $dir . DS . $file;
            $imageCheck = imagecreatefrompng($fileFull);
            $imageSize  = getimagesize($fileFull);
            $xMax = $xMaxs[$number] = $imageSize[0];
            $yMax = $imageSize[1];
            for ($x = 0; $x < $xMax; $x ++) {
                for ($y = 0; $y < $yMax; $y ++) {
                    $cnt ++;
                    if (
                        Gd::isWhite($image, $x + $xFrom, $y + $yFrom, $isNegative) !=
                        Gd::isWhite($imageCheck, $x, $y)
                    ) {
                        $errors[$number] ++;
                    }
                }
            }
            $errors[$number] /= $cnt;
        }
        asort($errors);
        $keys   = array_keys($errors);
        $xFrom += $errors[$keys[0]] < 0.25 ? $xMaxs[$keys[0]] + 1 : 20;
        return $errors[$keys[0]] < 0.25 ? intval($keys[0]) : "";
    }
    // /api/v1/WorkPlaces/143604 143612 144046

    /**
     *
     * @param string $tmpFile
     * @return array
     */
    static public function getRowsFromFile(string $tmpFile = "")
    {
        $image  = imagecreatefromjpeg($tmpFile);
        $width  = imagesx($image);
        if (576 == $width) {
            $size     = 64;
            $xFrom    = 6;
            $yFrom    = 184;
        } elseif (582 == $width) {
            $size     = 64.67;
            $xFrom    = 6;
            $yFrom    = 188;
        }
        $rows = [];
        for ($row = 0; $row < 9; $row ++) {
            for ($col = 0; $col < 9; $col ++) {
                $errors = [];
                for ($val = 0; $val <= 9; $val ++) {
                    $errors[$val] = 0;
                    $imageCheck = imagecreatefrompng(APP_DIR . "resources" . DS . "sud" . DS . "{$val}.png");
                    for ($x = 0; $x < 56; $x ++) {
                        for ($y = 0; $y < 56; $y ++) {
                            $rgb = imagecolorat(
                                $image,
                                intval($x + $xFrom + ($col * $size)),
                                intval($y + $yFrom + ($row * $size))
                            );
                            $r = ($rgb >> 16) & 0xFF;
                            $g = ($rgb >> 8) & 0xFF;
                            $b = $rgb & 0xFF;
                            $rgbCheck = imagecolorat($imageCheck, $x, $y);
                            if ($r < 96 && $g < 96 && $b < 96) {
                                if (!$rgbCheck) {
                                    $errors[$val] ++;
                                }
                            } else {
                                if ($rgbCheck) {
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

    /**
     *
     * @param array $params
     */
    static public function getRowsFromRequest(&$params = [])
    {
        if (!$_FILES) {
            return;
        }
        $params["rows"] = self::getRowsFromFile($_FILES["file"]["tmp_name"]);
    }

    /**
     *
     * @param array $resultRows
     * @param array $startRows
     * @param number $level
     * @param string $gadget
     * @return string
     */
    static public function saveAc($resultRows = [], $startRows = [], $level = null, $gadget = "Mi11")
    {
        self::$scenarioId = rand(10, 99); //intval(microtime(true) * 1000);
        if (!$level) {
            $level = time();
            $return = "{$gadget}.{$level}";
        } else {
            $level = sprintf("%05d", $level);
            $return = "{$gadget}.{$level}." . time();
        }
        $data = [
            "name" => "{$gadget} {$level}",
            "mode" => 2,
            "gapBetweenCyc" => 10,
            "isCycle" => true,
            "cycleType" => 2,
            "cycleDuration" => 10,
            "cycleReps" => 1,
            "items" => []
        ];
        for ($button = 1; $button < 10; $button ++) {
            $data["items"][] = self::getAcButton($button, $gadget);
            foreach ($resultRows as $y => $row) {
                foreach ($row as $x => $val) {
                    if ($val == $button && empty($startRows[$y][$x])) {
                        $data["items"][] = self::getAcField($x, $y, $gadget);
                    }
                }
            }
        }
        $cntItems = count($data["items"]);
        $return .= ".{$cntItems}";
        $data["name"] .= " {$cntItems}";
        file_put_contents(TMP_DIR . "{$return}.txt", json_encode($data));
        return $return;
    }

    /**
     *
     * @param array $resultRows
     * @param array $startRows
     * @param number $level
     * @param string $gadget
     * @return string
     */
    static public function saveAcc($resultRows = [], $startRows = [], $level = null, $gadget = "Mi11")
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
            'name' => "{$gadget} {$level}",
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
        $cntTargets = count($data['targets']);
        $return .= ".{$cntTargets}";
        $data["name"] .= " {$cntTargets}";
        file_put_contents(TMP_DIR . "{$return}.txt", json_encode([$data]));
        return $return;
    }

    /**
     *
     * @param array $startSolution
     * @return array
     */
    static public function solve($startSolution = [])
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

    /**
     *
     * @param number $val
     * @param string $gadget
     * @return []
     */
    static private function getAcButton($val = 0, $gadget = "Mi11")
    {
        $val --;
        if ($val < 0) {
            $val = 9;
        }
        switch ($gadget) {
            case "Mi11":
                $xDelta   = 200;
                $xOffset  = 60;
                $yDelta   = 475;
                $yOffset  = 1525;
                break;
            case "Pad6":
                $xDelta   = 320;
                $xOffset  = 120;
                $yDelta   = 360;
                $yOffset  = 2180;
                break;
            case "x9d":
                $xDelta   = 200;
                $xOffset  = 100;
                $yDelta   = 500;
                $yOffset  = 1700;
                break;
        }
        return [
            "scenarioId" => self::$scenarioId,
            "priority" => 0,
            "type" => "CLICK",
            "gapNext" => 10,
            "x" => ($val % 5) * $xDelta + $xOffset,
            "y" => floor($val / 5) * $yDelta + $yOffset,
            "repeatCount" => 1,
            "clickDuration" => 10,
            "cdShowType" => 0
        ];
    }

    /**
     *
     * @param number $val
     * @param string $gadget
     * @return []
     */
    static private function getAccButton($val = 0, $gadget = "Mi11")
    {
        $val --;
        if ($val < 0) {
            $val = 9;
        }
        switch ($gadget) {
            case "Mi11":
                $xDelta   = 200;
                $xOffset  = 60;
                $yDelta   = 475;
                $yOffset  = 1525;
                break;
            case "Pad6":
                $xDelta   = 320;
                $xOffset  = 120;
                $yDelta   = 360;
                $yOffset  = 2180;
                break;
            case "x9d":
                $xDelta   = 200;
                $xOffset  = 100;
                $yDelta   = 475;
                $yOffset  = 1525;
                break;
        }
        return [
            "delayUnit"  => 0,
            "delayValue" => 10,
            "duration"   => 0,
            "type"  => 0,
            "xPos"  => ($val % 5) * $xDelta + $xOffset,
            "xPos1" => -1,
            "yPos"  => floor($val / 5) * $yDelta + $yOffset,
            "yPos1" => -1
        ];
    }

    /**
     *
     * @param number $x
     * @param number $y
     * @param string $gadget
     * @return []
     */
    static private function getAcField($x = 0, $y = 0, $gadget = "Mi11")
    {
        switch ($gadget) {
            case "Mi11":
                $xDelta   = 120;
                $xOffset  = 50;
                $yDelta   = 125;
                $yOffset  = 370;
                break;
            case "Pad6":
                $xDelta   = 200;
                $xOffset  = 100;
                $yDelta   = 205;
                $yOffset  = 360;
                break;
            case "x9d":
                $xDelta   = 130;
                $xOffset  = 75;
                $yDelta   = 135;
                $yOffset  = 430;
                break;
        }
        return [
            "scenarioId" => self::$scenarioId,
            "priority" => 0,
            "type" => "CLICK",
            "gapNext" => 10,
            "x" => $xOffset + $x * $xDelta,
            "y" => $yOffset + $y * $yDelta,
            "repeatCount" => 1,
            "clickDuration" => 10,
            "cdShowType" => 0
        ];
    }

    /**
     *
     * @param number $x
     * @param number $y
     * @param string $gadget
     * @return []
     */
    static private function getAccField($x = 0, $y = 0, $gadget = "Mi11")
    {
        switch ($gadget) {
            case "Mi11":
                $xDelta   = 120;
                $xOffset  = 50;
                $yDelta   = 125;
                $yOffset  = 370;
                break;
            case "Pad6":
                $xDelta   = 200;
                $xOffset  = 100;
                $yDelta   = 205;
                $yOffset  = 360;
                break;
            case "x9d":
                $xDelta   = 130;
                $xOffset  = 65;
                $yDelta   = 125;
                $yOffset  = 370;
                break;
        }
        return [
            "delayUnit"  => 0,
            "delayValue" => 10,
            "duration"   => 0,
            "type"  => 0,
            "xPos"  => $xOffset + $x * $xDelta,
            "xPos1" => -1,
            "yPos"  => $yOffset + $y * $yDelta,
            "yPos1" => -1
        ];
    }

    /**
     *
     * @param number $rowIndex
     * @param number $columnIndex
     * @param array $puzzle
     * @return []
     */
    static private function getBlockValues($rowIndex = 0, $columnIndex = 0, $puzzle = [])
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

    /**
     *
     * @param number $columnIndex
     * @param array $puzzle
     * @return []
     */
    static private function getColumnValues($columnIndex = 0, $puzzle = [])
    {
        $values = [];
        for ($r = 0; $r < 9; ++$r) {
            $values[] = $puzzle[$r][$columnIndex];
        }
        return $values;
    }

    /**
     *
     * @param number $rowIndex
     * @param array $puzzle
     * @return []
     */
    static private function getRowValues($rowIndex = 0, $puzzle = [])
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

    static private $scenarioId = 0;
}