<?php

ini_set("memory_limit", -1);

require_once(INC_DIR . "Storage/PgSqlStorage.php");
require_once(INC_DIR . "Storage/MySqlStorage.php");

class WsClass extends PgSqlStorage
{

    /**
     *
     * @param array $bootles
     */
    public function setBootles(array $bootles)
    {
        $this->bootles = $bootles;
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
     * @return array
     */
    public function solve()
    {
        $result = [
            'success'   => true,
            'start'     => date("H:i:s"),
            'finish'    => date("H:i:s")
        ];
        if ($colors = $this->checkColors()) {
            return [
                'success' => false,
                'colors' => $colors
            ];
        }
        $wsRow = $this->selectRow("ws", ['level' => $this->level, 'solved' => true]);
        if ($wsRow) {
            $result['moves'] = $this->selectRows("ws", ['level' => $this->level, "step > 0"], "step");
            return $result;
        }
        $this->query("DELETE FROM ws WHERE level = {$this->level} AND step > 0");
        $this->query("vacuum full analyse ws");
        $this->log->add("DELETE&vacuum done");
        $step   = 1;
        if ($this->bootles) {
            $bootlesJson    = json_encode($this->bootles);
        } else {
            $wsLevelsRow = $this->selectRow("ws_levels", ['level' => $this->level]);
            if (!$wsLevelsRow) {
                return $result;
            }
            $bootlesJson = $wsLevelsRow['bootles'];
            $this->bootles = json_decode($bootlesJson, true);
        }
        $hash   = strval(md5("{$bootlesJson};0;0;"));
        $row    = [
            'bootles'   => $bootlesJson,
            'from'  => 0,
            'hash'  => $hash,
            'hash_parent'   => "",
            'level'         => $this->level,
            'solved'    => false,
            'step'  => 0,
            'to'    => 0
        ];
        $this->insertWsRow($row);
        $this->nextStep($this->bootles, $step, $hash);
        $result['finish'] = date("H:i:s");
        if (!$this->solved) {
            $result['success'] = false;
            return $result;
        }
        $this->insertWsRows();
        $wsRow = $this->selectRow("ws", ['solved' => true, 'level' => $this->level], "", "step");
        $this->query("DELETE FROM ws WHERE level = {$wsRow['level']} AND step > {$wsRow['step']}");
        while ($wsRow['step'] > 0) {
            $step = $wsRow['step'];
            $this->query("DELETE FROM ws WHERE level = {$wsRow['level']} AND step = {$wsRow['step']} AND hash <> '{$wsRow['hash']}'");
            $wsRow = $this->selectRow("ws", ['hash' => $wsRow['hash_parent'], 'level' => $wsRow['level'], "step < {$wsRow['step']}"]);
            $this->query("DELETE FROM ws WHERE level = {$wsRow['level']} AND step > {$wsRow['step']} AND step < {$step}");
        }
        $result['moves'] = $this->selectRows("ws", ['level' => $this->level, "step > 0"], "step");
        return $result;
    }

    /**
     *
     * @return array
     */
    private function checkColors()
    {
        $colors = [];
        foreach ($this->bootles as $bootle) {
            foreach ($bootle as $color) {
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
     * @param array $bootleFrom
     * @param array $bootleTo
     * @return boolean
     */
    private function checkMove(array $bootleFrom, array $bootleTo)
    {
        if (!$bootleFrom[3]) {
            // нечего перемещать
            return false;
        }
        if ($bootleTo[0]) {
            // некуда перемещать
            return false;
        }
        $cntFrom        = 0;
        $cntTotalFrom   = 0;
        $colorFrom      = "";
        foreach ($bootleFrom as $_colorFrom) {
            if (!$_colorFrom) {
                continue;
            }
            $cntTotalFrom ++;
            if (!$colorFrom) {
                $colorFrom = $_colorFrom;
            }
            if ($colorFrom == $_colorFrom) {
                $cntFrom ++;
            }
        }
        if (!$bootleTo[3] && $cntTotalFrom == $cntFrom) {
            // не имеет смысла перемещать всё в "пустоту"
            return false;
        }
        $cntFreeTo = 0;
        foreach ($bootleTo as $colorTo) {
            if (!$colorTo) {
                $cntFreeTo ++;
                continue;
            }
            if ($colorFrom == $colorTo) {
//                 if ($cntFreeTo >= $cntFrom) {
//                     return true;
//                 }
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     *
     * @param array $bootles
     * @return boolean
     */
    private function checkSolved(array $bootles)
    {
        foreach ($bootles AS $bootle) {
            if (count(array_unique($bootle)) > 1) {
                return false;
            }
        }
        $this->solved = true;
        return true;
    }

    /**
     *
     * @param array $row
     */
    private function insertWsRow(array $row = [])
    {
        $row['level'] = $this->level;
        if (isset($row['bootles'])) {
            $this->insertOrUpdateRow("ws_levels", $row, ['level']);
            unset($row['bootles']);
        }
        $this->hashes[$row['hash']] = $row;
        $this->hasheCnt ++;
        if ($this->hasheCnt % 1000000 != 0) {
            return;
        }
        $cnt = count($this->hashes);
        $this->log->add("{$this->hasheCnt}: {$cnt}");
        if ($cnt < 5000000) {
            return;
        }
        $this->insertWsRows();
        $this->hashes = [];
    }

    private function insertWsRows()
    {
        $affectedRows = 0;
        foreach (array_chunk($this->hashes, 10000) as $hashes) {
            $affectedRows += $this->insertOrUpdateRows("ws", $hashes, ['hash', 'level']);
        }
        $this->log->add("affectedRows: {$affectedRows}");
        $this->query("vacuum full analyse ws");
        $this->log->add("vacuum done");
    }

    /**
     *
     * @param array $bootles
     * @param int $from
     * @param int $to
     * @return array
     */
    private function move(array $bootles, int $from, int $to)
    {
        $colorMove = "";
        foreach ($bootles[$from] as $keyFrom => $colorFrom) {
            if (!$colorFrom) {
                continue;
            }
            if (!$colorMove) {
                $colorMove = $colorFrom;
            }
            if ($colorMove != $colorFrom) {
                break;
            }
            for ($keyTo = count($bootles[$to]) - 1; $keyTo >= 0; $keyTo --) {
                if ($bootles[$to][$keyTo]) {
                    continue;
                }
                $bootles[$to][$keyTo] = $colorMove;
                $bootles[$from][$keyFrom] = "";
                break;
            }
        }
        return $bootles;
    }

    /**
     *
     * @param array $bootles
     * @param int $step
     * @param string $hashParent
     */
    private function nextStep(array $bootles = [], int $step = 0, string $hashParent = "", $prevFrom = 0, $prevTo = 0)
    {
        if ($this->solved) {
            return;
        }
        $bootlesJson = json_encode($bootles);
        $keysFrom    = array_keys($bootles);
        shuffle($keysFrom);
        foreach ($keysFrom as $keyFrom) {
            $bootleFrom = $bootles[$keyFrom];
//        foreach ($bootles as $keyFrom => $bootleFrom) {
            foreach ($bootles as $keyTo => $bootleTo) {
                if ($keyFrom == $keyTo) {
                    continue;
                }
                if ($keyFrom == $prevTo && $keyTo == $prevFrom) {
                    continue;
                }
                if (!$this->checkMove($bootleFrom, $bootleTo)) {
                    continue;
                }
                $hash = strval(md5("{$bootlesJson};{$keyFrom};{$keyTo};"));
                $wsRow = $this->selectWsRow($hash);
                if ($wsRow && $wsRow['step'] <= $step) {
                    // уже был такой переход с данной позиции, на этом или более предпочтительном шаге
                    continue;
                }
                $_bootles = $this->move($bootles, $keyFrom, $keyTo);
                if (!$wsRow || $step < $wsRow['step']) {
                    $this->insertWsRow([
                        'from'  => $keyFrom,
                        'hash'  => $hash,
                        'hash_parent'   => $hashParent,
                        'level'         => $this->level,
                        'solved'    => $this->checkSolved($_bootles),
                        'step'  => $step,
                        'to'    => $keyTo
                    ]);
                }
                if ($this->solved) {
                    return;
                }
                $this->nextStep($_bootles, $step + 1, $hash, $keyFrom, $keyTo);
            }
        }
    }

    /**
     *
     * @param string $hash
     * @return array
     */
    private function selectWsRow($hash = "")
    {
        if (empty($this->hashes[$hash])) {
            $wsRow = $this->selectRow("ws", ['hash'  => $hash, 'level' => $this->level]);
            if (!$wsRow) {
                return [];
            }
            $this->hashes[$hash] = [
                'from'  => $wsRow['from'],
                'hash'  => $wsRow['hash'],
                'hash_parent'   => $wsRow['hash_parent'],
                'level' => $wsRow['level'],
                'solved'    => $wsRow['solved'],
                'step'  => $wsRow['step'],
                'to'    => $wsRow['to']
            ];
        }
        return $this->hashes[$hash];
    }

    private $bootles = [];

    private $hashes  = [];

    private $hasheCnt     = 0;

    private $hasheCntMax  = 5000000; //16777216;

    private $level     = 0;

    private $solved = false;

}

