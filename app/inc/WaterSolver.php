<?php

namespace app\inc;

use app\inc\Storage\PgsqlStorage;

ini_set("memory_limit", -1);

require_once(INC_DIR . "Storage/PgSqlStorage.php");
//require_once(INC_DIR . "Storage/MySqlStorage.php");

class WaterSolver extends PgSqlStorage
{

    /**
     *
     * @param string $file
     * @return number
     */
    static public function getLevelFromFile(string $file)
    {
        $level = 0;
        $im     = imagecreatefromjpeg($file);
        $coordx = [316, 293, 270, 247];
        foreach ($coordx as $pow => $startX) {
            $errors = [];
            for ($num = 0; $num < 10; $num ++) {
                $imageCheck = imagecreatefrompng(APP_DIR . "resources" . DS . "ws" . DS . "level" . DS . "{$num}.png");
                $errors[$num] = 0;
                for ($x = 0; $x < 20; $x ++) {
                    for ($y = 0; $y < 30; $y ++) {
                        $rgb = imagecolorat($im, $x + $startX, $y + 158);
                        $r = ($rgb >> 16) & 0xFF;
                        $g = ($rgb >> 8) & 0xFF;
                        $b = $rgb & 0xFF;
                        $rgbCheck = imagecolorat($imageCheck, $x, $y);
                        if ($r < 127 && $g < 127 && $b < 127) {
                            if ($rgbCheck) {
                                $errors[$num] ++;
                            }
                        } else {
                            if (!$rgbCheck) {
                                $errors[$num] ++;
                            }
                        }
                    }
                }
            }
            asort($errors);
            $keys = array_keys($errors);
            if ($errors[$keys[0]] < 100) {
                $level += intval(pow(10, $pow)) * intval($keys[0]);
            }
        }
        return $level;
    }

    /**
     *
     * @return string
     */
    public function saveAcc()
    {
        $levelsRow  = $this->selectRow("ws_levels", ["level" => $this->level]);
        $moves  = $this->getMovesFromLevel($this->level);
        $data   = [
            "antiDetection" => true,
            "id"    => 0,
            "name"  => "WS.{$this->level}." . count($moves) . "." . $this->getDelay($moves),
            "numberOfCycles"    => 1,
            "stopConditionChecked"  => 2,
            "targets"   => [],
            "timeValue" => 400
        ];
        $bootlePoints = [];
        $points = $this->points[count(json_decode($levelsRow["bottles"], true))];
        foreach ($points as $row) {
            $stepX = ($row[1]["x"] - $row[0]["x"]) / ($row[1]["bottle"] - $row[0]["bottle"]);
            $y = intval($row[0]["y"] * 2400 / 1280);
            for ($bottle = $row[0]["bottle"]; $bottle <= $row[1]["bottle"]; $bottle ++) {
                $bootlePoints[$bottle] = [
                    "x" => intval(($row[0]["x"] + ($bottle - $row[0]["bottle"]) * $stepX) * 1080 / 576),
                    "y" => $y
                ];
            }
        }
        foreach ($moves as $move) {
            $data['targets'][]  = [
                "delayUnit"     => 0,
                "delayValue"    => $this->minDelay,
                "duration"  => 0,
                "type"  => 0,
                "xPos"  => $bootlePoints[$move["from"]]["x"],
                "xPos1" => -1,
                "yPos"  => $bootlePoints[$move["from"]]["y"],
                "yPos1" => -1
            ];
            $data['targets'][] = [
                "delayUnit"     => 0,
                "delayValue"    => $move["delay"],
                "duration"  => 0,
                "type"  => 0,
                "xPos"  => $bootlePoints[$move["to"]]["x"],
                "xPos1" => -1,
                "yPos"  => $bootlePoints[$move["to"]]["y"],
                "yPos1" => -1
            ];
        }
        $return = $data["name"] . "." . time();
        file_put_contents(TMP_DIR . "{$return}.txt", json_encode([$data]));
        return $return;
    }

    /**
     *
     * @param array $result
     */
    public function saveMovies(array &$result, $proc = 0)
    {
        if ($this->saveToDb) {
            $this->insertWsTmpRows();
        }
        if (!$this->solved) {
            return;
        }
        $result["moves"] = [];
        $result["proc"]  = $proc;
        $wsTmpRow = $this->getLastStepRow();
        while ($wsTmpRow && $wsTmpRow["step"] > 0) {
            $wsTmpRow["level"] = $this->level;
            $wsTmpRow["proc"]  = $proc;
            $result["moves"][] = $wsTmpRow;
            $wsTmpRow = $this->getParentRow($wsTmpRow);
        }
        $this->query("DELETE FROM ws WHERE level = {$result["level"]} and proc = {$result["proc"]}");
        $this->insertOrUpdateRows("ws", $result["moves"], ["level", "proc", "step"]);
        $result["moves"] = $this->getMovesFromProc($result["level"], $result["proc"]);
        $this->updateDelays($result["moves"], $proc);
    }

    /**
     *
     * @param array $bottles
     * @return array
     */
    public function setBottles(array $bottles)
    {
        return $this->bottles = $bottles;
    }

    /**
     *
     * @param string $file
     * @param array $colors
     * @return array
     */
    public function setBottlesFromFile(string $file, array $colors)
    {
        $this->bottles  = [];
        $im     = imagecreatefromjpeg($file);
        $points = $this->points[count($colors) + 1];
        foreach ($points as $row) {
            $stepX = ($row[1]["x"] - $row[0]["x"]) / ($row[1]["bottle"] - $row[0]["bottle"]);
            $stepY = ($row[1]["y"] - $row[0]["y"]) / 3;
            for ($bottle = $row[0]["bottle"]; $bottle <= $row[1]["bottle"]; $bottle ++) {
                $this->bottles[$bottle] = [];
                for ($stage = 0; $stage < 4; $stage ++) {
                    $this->bottles[$bottle][$stage] = 0;
                    for ($x = $row[0]["x"] + ($bottle - $row[0]["bottle"]) * $stepX - 2;
                        $x <= $row[0]["x"] + ($bottle - $row[0]["bottle"]) * $stepX + 2;
                        $x ++
                    ) {
                        for ($y = $row[0]["y"] + $stage * $stepY - 2;
                            $y <= $row[0]["y"] + $stage * $stepY + 2;
                            $y ++
                        ) {
                            $rgb   = imagecolorat($im, $x, $y);
                            $r = ($rgb >> 16) & 0xFF;
                            $g = ($rgb >> 8) & 0xFF;
                            $b = $rgb & 0xFF;
                            foreach ($colors as $colorKey => $colorCheck) {
                                $rgbCheck = hexdec(mb_substr($colorCheck, 1));
                                $rCheck = ($rgbCheck >> 16) & 0xFF;
                                $gCheck = ($rgbCheck >> 8) & 0xFF;
                                $bCheck = $rgbCheck & 0xFF;
                                if (abs($rCheck - $r) < 2 && abs($gCheck - $g) < 2 && abs($bCheck - $b) < 2) {
                                    $this->bottles[$bottle][$stage] = $colorKey;
                                    break 3;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $this->bottles;
    }

    /**
     *
     * @param int $cntColors
     * @param int $level
     * @return array
     */
    public function setData(int $cntColors = 15, int $level)
    {
        $result = [
            "bottles"   => [],
            "cntColors" => $cntColors,
            "colors"    => [],
            "level"     => $level
        ];
        $this->setLevel($level);
        $wsLevelRow = $this->selectRow("ws_levels", ["level" => $level]);
        if ($wsLevelRow) {
            $result["bottles"] = json_decode($wsLevelRow["bottles"], true);
            $this->setBottles($result["bottles"]);
        }
        switch ($result["cntColors"]) {
            case 11:
                $result['newLines'] = [5, 10];
                break;

            case 9:
                $result['newLines'] = [6];
                $result['colors'] = [
                    '#000000',
                    '#FF0000',
                    '#FFA500',
                    '#FFFF00',
                    '#00FF00',
                    '#FF00FF',
                    '#00008B',
                    '#800080',
                    '#20B2AA',
                    '#FF69B4'
                ];
                break;

            case 12:
                $result['newLines'] = [7];
                $result['colors'] = [
                    '#000000',
                    '#FF0000',
                    '#FFA500',
                    '#FFFF00',
                    '#00FF00',
                    '#0000AF',
                    '#007F00',
                    '#F032E6',
                    '#911E8E',
                    '#7F7FFF',
                    '#00E59D',
                    '#FF69B4',
                    '#C71585'
                ];
                break;

            case 13:
            case 15:
                $result['newLines'] = [6, 12];
                break;
        }
        if ($result["colors"]) {
            sort($result["colors"]);
            return;
        }
        $im = imagecreatefrompng(APP_DIR . "resources" . DS . "ws" . DS . $result["cntColors"] . ".png");
        if (!$im) {
            return;
        }
        $result['rgbRows'] = [];
        for ($y = 0; $y < imagesy($im); $y ++) {
            for ($x = 0; $x < imagesx($im); $x ++) {
                $rgb = sprintf("#%06X", imagecolorat($im, $x, $y));
                if (empty($result['rgbRows'][$rgb])) {
                    $result['rgbRows'][$rgb] = 0;
                }
                $result['rgbRows'][$rgb] ++;
            }
        }
        foreach ($result['rgbRows'] as $color => $cnt) {
            if ($cnt < 20000) {
                unset($result['rgbRows'][$color]);
            }
            if ($cnt > 30000) {
                unset($result['rgbRows'][$color]);
            }
            if ("#C1C1C1" == $color) {
                unset($result['rgbRows'][$color]);
            }
        }
        $result["colors"] = array_merge(["#000000"], array_keys($result["rgbRows"]));
        sort($result["colors"]);
        return $result;
    }

    /**
     *
     * @param int $level
     */
    public function setLevel(int $level)
    {
        $this->level = $level;
    }

    /**
     *
     * @param boolean $resolve
     * @param boolean $saveToDb
     * @param boolean $reverse
     * @return array
     */
    public function solve($resolve = false, $saveToDb = false, $reverse = false)
    {
        $this->reverse  = $reverse;
        $this->saveToDb = $saveToDb;
        $result = [
            "level"     => $this->level,
            "start"     => date("H:i:s"),
            "success"   => true
        ];
        $this->truncate();
        $this->hashes   = [];
        $this->hasheCnt = 0;
        $this->solved   = false;
        $this->logs->add("solve: {$this->level}");
        if (!$this->bottles) {
            $wsLevelsRow = $this->selectRow("ws_levels", ["level" => $this->level]);
            if (!$wsLevelsRow) {
                $result["success"] = false;
                return $this->result($result);
            }
            $this->bottles = json_decode($wsLevelsRow["bottles"], true);
        }
        $result["cntBottles"] = count($this->bottles);
        if ($colors = $this->checkColors()) {
            return [
                "success"   => false,
                "colors"    => $colors
            ];
        }
        $wsRow = $this->selectRow("ws", ["level" => $this->level]);
        if ($wsRow && !$resolve) {
            $result["moves"] = $this->getMovesFromLevel($this->level);
            return $this->result($result);
        }
        $this->insertOrUpdateRow(
            "ws_levels",
            [
                "bottles" => json_encode($this->bottles),
                "level"   => $this->level
            ],
            ["level"]
        );
        $this->nextStep($this->bottles);
        if (!$this->procs) {
            $this->saveMovies($result);
        }
        foreach ($this->procs as $proc => $_proc) {
            $stream = "";
            while ($buf = fgets($this->pipes[$proc][1])) {
                $stream .= $buf;
            }
            $status = proc_get_status($_proc);
            if (!$status["running"]) {
                fclose($this->pipes[$proc][1]);
                fclose($this->pipes[$proc][2]);
                proc_close($_proc);
            }
            if (!$stream) {
                continue;
            }
            $resultProc = json_decode($stream, true);
            if (!isset($resultProc["solved"])) {
                $this->logs->add("{$proc}.stream: {$stream}");
                continue;
            }
            $this->logs->add("{$proc}.solved: {$resultProc["solved"]}; hasheCnt: {$resultProc["hasheCnt"]}");
            if (!$resultProc["solved"]) {
                continue;
            }
            $this->hasheCnt = $resultProc["hasheCnt"];
            $this->hashes   = $resultProc["hashes"];
            $this->solved   = true;
            $this->saveMovies($result, $proc);
        }
        if (!$this->solved) {
            $result["success"] = false;
            return $this->result($result);
        }
        $result["moves"] = $this->getMovesFromLevel($this->level);
        $this->truncate();
        $this->logs->add("cntMoves: " . count($result["moves"]));
        return $this->result($result);
    }

    /**
     *
     * @param string $paramsJson
     * @return array
     */
    public function solveStep($paramsJson = "")
    {
        $this->hasheCnt = 0;
        $this->solved   = false;
        $params         = json_decode($paramsJson, true);
        $this->hashes   = $params["hashes"];
        $this->proc     = $params["proc"];
        $this->reverse  = $params["reverse"];
        $this->saveToDb = $params["saveToDb"];
        $this->nextStep($params["bottles"], $params["step"], $params["hash"], $params["keyFrom"], $params["keyTo"]);
        return [
            "solved"    => $this->solved,
            "hasheCnt"  => $this->hasheCnt,
            "hashes"    => $this->hashes
        ];
    }

    /**
     *
     * @return array
     */
    private function checkColors()
    {
        $colors = [];
        foreach ($this->bottles as $bottle) {
            foreach ($bottle as $color) {
                if (!$color) {
                    continue;
                }
                if (empty($colors[$color])) {
                    $colors[$color] = 0;
                }
                $colors[$color] ++;
            }
        }
        foreach ($colors as $color => $cnt) {
            if (4 !== $cnt) {
                return $colors;
            }
        }
    }

    /**
     *
     * @param array $bottles
     * @return boolean
     */
    private function checkSolved(array $bottles)
    {
        if ($this->solved) {
            return true;
        }
        foreach ($bottles AS $bottle) {
            $firstColor = $bottle[0];
            foreach ($bottle as $color) {
                if ($firstColor != $color) {
                    return $this->solved = false;
                }
            }
        }
        return $this->solved = true;
    }

    /**
     *
     * @param array $moves
     * @return number
     */
    private function getDelay($moves = [])
    {
        return array_sum(array_map(function($row) { return $row["delay"]; }, $moves));
    }

    private function getLastStepRow()
    {
        if ($this->saveToDb) {
            return $this->selectRow("ws_tmp", [], "step DESC");
        }
        $maxStep   = 0;
        $returnRow = [];
        foreach ($this->hashes as $hash) {
            if ($maxStep > $hash["step"]) {
                continue;
            }
            $maxStep    = $hash["step"];
            $returnRow  = $hash;
        }
        return $returnRow;
    }

    /**
     *
     * @param int $level
     * @return array
     */
    private function getMovesFromLevel($level = 0)
    {
        $proc = $this->queryOne("
SELECT proc
  FROM ws
 WHERE level = {$this->level}
 GROUP BY proc
 ORDER BY SUM(delay) ASC, COUNT(*) ASC
 LIMIT 1");
        return $this->getMovesFromProc($level, $proc);
    }

    /**
     *
     * @param int $level
     * @param int $proc
     * @return array
     */
    private function getMovesFromProc($level = 0, $proc = 0)
    {
        return $this->selectRows("ws", ["level" => $level, "proc" => $proc, "step > 0"], "step");
    }

    /**
     *
     * @param array $wsTmpRow
     * @return array
     */
    private function getParentRow($wsTmpRow = [])
    {
        if ($this->saveToDb) {
            return $this->selectRow(
                "ws_tmp",
                [
                    "hash" => $wsTmpRow["hash_parent"],
//                    "proc" => $this->proc,
                    "step < {$wsTmpRow["step"]}"
                ]
            );
        }
        foreach ($this->hashes as $hash) {
            if ($hash["hash"] == $wsTmpRow["hash_parent"] && $hash["step"] < $wsTmpRow["step"]) {
                return $hash;
            }
        }
        return [];
    }
    /**
     *
     * @param array $bottles
     * @param int $from
     * @param int $to0
     * @return string
     */
    private function hash(array $bottles, int $from, int $to)
    {
        return md5(json_encode($bottles)); // . ($this->extendedHash ? "{$from}:{$to}" : ""));
//         $str = "";
//         foreach ($bottles as $bottle) {
//             foreach ($bottle as $cell) {
//                 $str .= dechex($cell);
//             }
//         }
//         if ($this->extendedHash) {
//             $str .= base_convert($from, 10, count($bottles)) . base_convert($to, 10, count($bottles));
//         }
//         return $str;
    }

    /**
     *
     * @param array $row
     */
    private function insertWsRow(array $row)
    {
        $this->hashes[$row["hash"]] = $row;
        $this->hasheCnt ++;
        if (!isset($this->hashesCnt[$row["step"]])) {
            $this->hashesCnt[$row["step"]] = 0;
        }
        $this->hashesCnt[$row["step"]] ++;
        if ($this->hasheCnt % 1000000 != 0) {
            return;
        }
        $cnt = count($this->hashes);
        $this->logs->add("{$this->proc}.{$this->hasheCnt}: {$cnt}");
        if ($this->hasheCnt % 10000000 != 0) {
            return;
        }
        if ($this->hasheCnt % pow(10, floor(log10($this->hasheCnt))) == 0) {
            $this->hashesCnt = array_count_values(array_column($this->hashes, "step"));
            ksort($this->hashesCnt);
            $this->logs->add("{$this->proc}." . var_export($this->hashesCnt, true));
        }
        if (!$this->saveToDb || $cnt < $this->hasheCntChunk) {
            return;
        }
        $this->insertWsTmpRows();
        $this->hashes = [];
    }

    /**
     *
     * @return number
     */
    private function insertWsTmpRows()
    {
        if (!$this->saveToDb) {
            return 0;
        }
        $affectedRows = 0;
        foreach (array_chunk($this->hashes, 100000) as $hashes) {
            $affectedRows += $this->insertOrUpdateRows("ws_tmp", $hashes, ["hash"]);
        }
        $this->logs->add("affectedRows: {$affectedRows}");
        $this->vacuum();
        $this->useDb = true;
        return $affectedRows;
    }

    /**
     *
     * @param array $bottles
     * @param int $step
     * @param string $hashParent
     * @param int $prevFrom
     * @param int $prevTo
     */
    private function nextStep(array $bottles, int $step = 1, string $hashParent = "", int $prevFrom = 0, int $prevTo = 0)
    {
        $this->checkSolved($bottles);
        if ($this->solved) {
            return;
        }
        $keysFrom = $keysTo = array_keys($bottles);
//         shuffle($keysFrom);
//         shuffle($keysTo);
        foreach ($keysFrom as $keyFrom) {
            foreach ($keysTo as $keyTo) {
                if ($keyFrom == $keyTo) {
                    continue;
                }
                if (!$this->reverse && $keyFrom == $prevTo && $keyTo == $prevFrom) {
                    continue;
                }
                if (!$bottles[$keyFrom][3]) {
                    // нечего перемещать (нижняя яейка пуста)
                    continue;
                }
                if ($bottles[$keyTo][0]) {
                    // некуда перемещать (вехня ячейка занята)
                    continue;
                }
                $bottleFrom = array_filter($bottles[$keyFrom]);
                if (
                    !$bottles[$keyTo][3] and 1 == count(array_unique($bottleFrom))
                ) {
                    // все исходные колонки одного цвета, то не имеет смысла перемещать "всё" в "пустоту"
                    continue;
                }
                $colorFrom = reset($bottleFrom);
                // поучен верхний цвет
                foreach ($bottles[$keyTo] as $colorTo) {
                    if ($colorTo) {
                        if ($colorFrom === $colorTo) {
                            break; // если цвета вехних ячеек совпадают
                        } else {
                            continue 2;
                        }
                    }
                }
                if ($this->extendedHash) {
                    $hash  = $this->hash($bottles, $keyFrom, $keyTo);
                    $wsRow = $this->selectWsRow($hash);
                    if ($wsRow && $wsRow["step"] <= $step) {
                        // уже был такой переход с данной позиции, на этом или более предпочтительном шаге
                        continue;
                    }
                }
                $rowTo = 4;
                $bottlesAfterMove = $bottles;
                foreach ($bottlesAfterMove[$keyFrom] as $rowFrom => $color) {
                    if (!$color) {
                        continue;
                    }
                    if ($color !== $colorFrom) {
                        break;
                    }
                    while ($rowTo -- > 0) {
                        if ($bottlesAfterMove[$keyTo][$rowTo]) {
                            continue;
                        }
                        $bottlesAfterMove[$keyFrom][$rowFrom] = 0;
                        $bottlesAfterMove[$keyTo][$rowTo] = $color;
                        break;
                    }
                }
                if (!$this->extendedHash) {
                    $hash  = $this->hash($bottlesAfterMove, $keyFrom, $keyTo);
                    $wsRow = $this->selectWsRow($hash);
                    if ($wsRow && $wsRow["step"] <= $step) {
                        //  уже был такой переход с данной позиции, на этом или более предпочтительном шаге
                        continue;
                    }
                }
                $this->insertWsRow([
                    "step"  => $step,
                    "hash"  => $hash,
                    "hash_parent"   => $hashParent,
                    "from"  => $keyFrom,
                    "to"    => $keyTo
                ]);
                if (0 == $step) {
                    $proc = count($this->procs);
                    $params = [
                        "bottles"   => $this->extendedHash ? $bottles : $bottlesAfterMove,
                        "hash"      => $hash,
                        "hashes"    => $this->hashes,
                        "keyFrom"   => $keyFrom,
                        "keyTo"     => $keyTo,
                        "proc"      => $proc,
                        "reverse"   => $this->reverse,
                        "saveToDb"  => $this->saveToDb,
                        "step"      => $step + 1
                    ];
                    $descriptorspec = [
                        0 => ["pipe", "r"], // stdin
                        1 => ["pipe", "w"], // stdout
                        2 => ["pipe", "w"] // stderr
                    ];
                    $this->streams[$proc] = "";
                    $this->procs[$proc] = proc_open(
                        "php " . APP_DIR . "robot" . DS . "wss.php " . addslashes(json_encode($params)),
                        $descriptorspec,
                        $this->pipes[$proc]
                    );
                    fclose($this->pipes[$proc][0]);
                    $this->logs->add("proc: {$proc}");
//                 } elseif (
//                     $step < 22 ||
//                     empty($this->hashesCnt[$step]) ||
//                     $this->hashesCnt[$step] < 100000 ||
//                     empty($this->hashesCnt[$step + 1]) ||
//                     $this->hashesCnt[$step + 1] < $this->hashesCnt[$step]
//                 ) {
//                     $this->nextStep($this->extendedHash ? $bottles : $bottlesAfterMove, $step + 1, $hash, $keyFrom, $keyTo);
                } else {
                    $this->nextStep($this->extendedHash ? $bottles : $bottlesAfterMove, $step + 1, $hash, $keyFrom, $keyTo);
                }
            }
        }
    }

    /**
     *
     * @param array $result
     * @return array
     */
    private function result(array $result)
    {
        $this->logs->add("hashesCnt: {$this->hasheCnt}");
        if ($result["success"]) {
            $result["cntMoves"] = count($result["moves"]);
            $result["delay"]    = $this->getDelay($result["moves"]);
        }
        $result["hashesCnt"]    = $this->hasheCnt;
        $result["finish"]       = date("H:i:s");
        return $result;
    }

    /**
     *
     * @param string $hash
     * @return array
     */
    private function selectWsRow(string $hash)
    {
        if (isset($this->hashes[$hash])) {
            return $this->hashes[$hash];
        }
        if (!$this->saveToDb || !$this->useDb) {
            return;
        }
        return $this->selectRow("ws_tmp", ["hash" => $hash]);
    }

    private function truncate()
    {
        if (!$this->saveToDb) {
            return;
        }
        $this->query("TRUNCATE TABLE ws_tmp");
    }

    /**
     *
     * @param array $move
     */
    private function updateMove(array $move)
    {
        $this->updateRow("ws", $move, ["level", "proc", "step"]);
    }

    /**
     *
     * @param array $moves
     */
    private function updateDelays(array &$moves, $proc)
    {
        $this->setUseCache(false);
        for ($key = count($moves) - 1; $key >= 0; $key --) {
            $move = $moves[$key];
            $nextFromMove = $this->queryRow("
SELECT *
  FROM ws
 WHERE level = {$this->level}
   AND proc = {$proc}
   AND step > {$move["step"]}
   AND (
       \"from\" = {$move["from"]} OR
       \"from\" = {$move["to"]} OR
       \"to\" = {$move["from"]}
    )
 ORDER BY step
 LIMIT 1");
            if (!$nextFromMove) {
                continue;
            }
            $delayRow = $this->queryRow("
SELECT SUM(delay) AS delay
  FROM ws
 WHERE level = {$this->level}
   AND proc = {$proc}
   AND step BETWEEN {$move["step"]} AND " . ($nextFromMove["step"] - 1));
            $move["delay"] = 2500 - $delayRow["delay"];
            if ($move["delay"] < $this->minDelay) {
                $move["delay"] = $this->minDelay;
            }
            $this->updateMove($move);
            $moves[$key] = $move;
        }
    }

    private function vacuum()
    {
        $this->query("vacuum full verbose analyse ws_tmp");
    }

    private $bottles        = [];

    private $extendedHash   = false;

    private $hashes     = [];

    private $hasheCnt   = 0;
    private $hashesCnt  = [];

    private $hasheCntChunk  = 100000000; // 67108864; //33554432; //16 777 216;

    private $level      = 0;

    private $minDelay   = 200;

    private $pipes      = [];

    private $points = [
        17 => [
            [
                [
                    "bottle" => 0,
                    "x" => 47,
                    "y" => 330
                ],
                [
                    "bottle" => 5,
                    "x" => 527,
                    "y" => 465
                ]
            ],
            [
                [
                    "bottle" => 6,
                    "x" => 47,
                    "y" => 650
                ],
                [
                    "bottle" => 11,
                    "x" => 527,
                    "y" => 785
                ]
            ],
            [
                [
                    "bottle" => 12,
                    "x" => 95,
                    "y" => 966
                ],
                [
                    "bottle" => 16,
                    "x" => 479,
                    "y" => 1101
                ]
            ]
        ]
    ];

    private $proc       = 0;

    private $procs      = [];

    private $reverse    = false;

    private $saveToDb   = false;

    private $solved     = false;

    private $streams    = [];

    private $useDb      = false;
}


/**

CREATE TABLE public.ws (
	"level" int4 NOT NULL,
	proc int2 DEFAULT 0 NOT NULL,
	step int2 DEFAULT 0 NOT NULL,
	"from" int2 DEFAULT 0 NOT NULL,
	"to" int2 DEFAULT 0 NOT NULL,
	created timestamp DEFAULT now() NOT NULL,
	delay int4 DEFAULT 100 NOT NULL,
	CONSTRAINT ws_pk PRIMARY KEY (level, proc, step)
);

CREATE TABLE public.ws_levels (
	"level" int4 NOT NULL,
	bottles json NOT NULL,
	created timestamp DEFAULT now() NULL,
	CONSTRAINT ws_levels_pk PRIMARY KEY (level)
);

CREATE TABLE public.ws_tmp (
	proc int2 DEFAULT 0 NOT NULL,
	step int2 DEFAULT 0 NOT NULL,
	hash varchar(100) NOT NULL,
	hash_parent varchar(100) DEFAULT ''::character varying NULL,
	"from" int2 DEFAULT 0 NOT NULL,
	"to" int2 DEFAULT 0 NOT NULL,
	CONSTRAINT ws_tmp_pk_1 PRIMARY KEY (hash, proc)
);

*/