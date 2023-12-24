<?php

require_once(INC_DIR . "Storage/PgSqlStorage.php");
//require_once(INC_DIR . "Storage/MySqlStorage.php");

class WsClass extends PgSqlStorage
{

    public function info()
    {
        phpinfo();
    }

    public function setBootles($bootles)
    {
        $this->bootles = $bootles;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function solve()
    {
        $result = [
            'success'   => false,
            'start'     => date("H:i:s"),
            'finish'    => date("H:i:s")
        ];
        if ($colors = $this->checkColors()) {
            return [
                'success' => false,
                'colors' => $colors
            ];
        }
        $this->query("DELETE FROM ws WHERE level = {$this->level} AND step > 0");
        $step   = 0;
        if ($this->bootles) {
            $bootlesJson    = json_encode($this->bootles);
        } else {
            $wsRow = $this->selectRow("ws", ['level' => $this->level, 'step' => 0]);
            if (!$wsRow) {
                return $result;
            }
            $bootlesJson = $wsRow['bootles'];
            $this->bootles = json_decode($bootlesJson, true);
        }
        $hash   = strval(md5("{$bootlesJson};0;0;"));
        $row    = [
            'bootles'   => $bootlesJson,
            'hash'      => $hash
        ];
        $this->insertWsRow($row);
        $this->nextStep($this->bootles, $step + 1, $hash);
        $result['finish'] = date("H:i:s");
        if (!$this->solved) {
            return $result;
        }
        $wsRow = $this->selectRow("ws", ['solved' => true, 'level' => $this->level], "", "step");
        $this->query("DELETE FROM ws WHERE level = {$wsRow['level']} AND step > {$wsRow['step']}");
        while ($wsRow['step'] > 0) {
            $step = $wsRow['step'];
            $this->query("DELETE FROM ws WHERE level = {$wsRow['level']} AND step = {$wsRow['step']} AND hash <> '{$wsRow['hash']}'");
            $wsRow = $this->selectRow("ws", ['hash' => $wsRow['hash_parent'], 'level' => $wsRow['level'], "step < {$wsRow['step']}"]);
            $this->query("DELETE FROM ws WHERE level = {$wsRow['level']} AND step > {$wsRow['step']} AND step < {$step}");
        }
        $result['success'] = true;
        $result['moves'] = $this->selectRows("ws", ['level' => $this->level, "step > 0"], "step");
        return $result;
    }

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

    private function checkMove($bootleFrom, $bootleTo)
    {
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
        if (!$cntFrom) {
            // нечего перемещать
            return false;
        }
        $cntFreeTo = 0;
        foreach ($bootleTo as $colorTo) {
            if (!$colorTo) {
                $cntFreeTo ++;
                continue;
            }
            return $colorFrom == $colorTo && $cntFreeTo >= $cntFrom;
        }
        if (!$cntFreeTo) {
            // некуда перемещать
            return false;
        }
        if (4 == $cntFreeTo && $cntTotalFrom == $cntFrom) {
            // не имеет смысла перемещать всё в пустоту
            return false;
        }
        return true;
    }

    private function checkSolved($bootles)
    {
        foreach ($bootles AS $bootle) {
            if (count(array_unique($bootle)) > 1) {
                return false;
            }
        }
        $this->solved = true;
        return true;
    }

    private function insertWsRow($row)
    {
        $row['level'] = $this->level;
        $this->insertOrUpdateRow("ws", $row, ['hash', 'level']);
    }

    private function move($bootles, $from, $to)
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

    /**-
     *
     * @param array $bootles
     * @param number $step
     * @param string $hashParent
     */
    private function nextStep($bootles = [], $step = 0, $hashParent = "")
    {
        if ($this->solved) {
            return;
        }
        $bootlesJson = json_encode($bootles);
        $keysFrom    = array_keys($bootles);
        shuffle($keysFrom);
        foreach ($keysFrom as $keyFrom) {
            $bootleFrom = $bootles[$keyFrom];
            foreach ($bootles as $keyTo => $bootleTo) {
                if ($keyFrom == $keyTo) {
                    continue;
                }
                if (!$this->checkMove($bootleFrom, $bootleTo)) {
                    continue;
                }
                $hash = strval(md5("{$bootlesJson};{$keyFrom};{$keyTo};"));
                $wsRow = $this->selectRow("ws", [
                    'hash'  => $hash,
                    'level' => $this->level
                ]);
                if ($wsRow && $wsRow['step'] <= $step - 0) {
                    // уже был такой переход с данной позиции
                    continue;
                }
                $_bootles = $this->move($bootles, $keyFrom, $keyTo);
                if (!$wsRow || $step < $wsRow['step']) {
                    $this->insertWsRow([
                        'bootles'   => $bootlesJson,
                        'from'  => $keyFrom,
                        'hash'  => $hash,
                        'hash_parent'   => $hashParent,
                        'solved'    => $this->checkSolved($_bootles),
                        'step'  => $step,
                        'to'    => $keyTo
                    ]);
                }
                if ($this->solved) {
                    return;
                }
                $this->nextStep($_bootles, $step + 1, $hash);
            }
        }
    }

    private function nextStepOld($bootles, $step, $hashParent, $from = 0, $to = 0)
    {
        if ($this->solved) {
            return;
        }
        foreach ($bootles as $keyFrom => $bootleFrom) {
            if (1 === count(array_unique($bootles[$keyFrom]))) {
                // с одним цветом
                continue;
            }
            foreach ($bootles as $keyTo => $bootleTo) {
                if ($keyFrom === $keyTo) {
                    continue;
                }
                if (!$this->checkMove($bootleFrom, $bootleTo)) {
                    continue;
                }
                $_bootles = $this->move($bootles, $keyFrom, $keyTo);
                $bootlesJson = json_encode($_bootles);
                $hash = md5($bootlesJson);
                if ($this->selectRow("ws", [
                    'from'  => $keyFrom,
                    'hash'  => $hash,
                    'hash_parent' => $hashParent,
                    'level' => $this->level,
                    'step'  => $step,
                    'to'    => $keyTo
                ])) {
                    // уже был переход на в такую позицию
                    continue;
                } else {
                    $this->insertWsRow([
                        'level' => $this->level,
                        'step'  => $step,
                        'from'  => $keyFrom,
                        'to'    => $keyTo,
                        'hash'  => $hash,
                        'hash_parent'  => $hashParent,
                        'solved'  => $this->checkSolved($_bootles),
                        'bootles' => $bootlesJson
                    ]);
                }
                if ($this->solved) {
                    return;
                }
                if ($keyFrom === $from && $keyTo === $to) {
                    continue;
                }
                $this->nextStep($_bootles, $step + 1, $hash, $keyTo, $keyFrom);
            }
        }
    }

    private $bootles = [];

    private $level = 0;

    private $solved = false;

}

