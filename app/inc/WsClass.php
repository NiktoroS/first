<?php

require_once(INC_DIR . "Storage/PgSqlStorage.php");
//require_once(INC_DIR . "Storage/MySqlStorage.php");

class WsClass extends PgSqlStorage
{

    public function info()
    {
        phpinfo();
    }

    public function solve()
    {
        $result = [
            'success' => true,
            'start' => date("H:i:s")
        ];
        if ($colors = $this->checkColors()) {
            return [
                'success' => false,
                'colors' => $colors
            ];
        }
        $this->query("DELETE FROM ws WHERE level = {$this->level}");
        $step   = 0;
        $bootlesJson    = json_encode($this->bootles);
        $hash   = md5($bootlesJson);
        $row    = [
            'bootles'   => $bootlesJson,
            'from'  => null,
            'hash'  => $hash,
            'hash_parent'   => "",
            'level' => $this->level,
            'step'  => $step,
            'to'    => null
        ];
        $this->insertOrUpdateRow("ws", $row, ['hash', 'hash_parent', 'level', 'step']);
        $this->nextStep($this->bootles, $step + 1, $hash);
        $result['finish'] = date("H:i:s");
        if (!$this->solved) {
            $result['success'] = false;
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
        $result['moves'] = $this->selectRows("ws", ['level' => $this->level, "step <> 0"], "step");
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
        $cntFrom   = 0;
        $cntTotalFrom = 0;
        $colorFrom = "";
        foreach ($bootleFrom as $_colorFrom) {
            if (!$colorFrom && $_colorFrom) {
                $colorFrom = $_colorFrom;
            }
            if ($_colorFrom) {
                $cntTotalFrom ++;
                if ($colorFrom == $_colorFrom) {
                    $cntFrom ++;
                }
            }
        }
        if (!$cntFrom) {
            return false;
        }
        $cntFreeTo = 0;
        foreach ($bootleTo as $colorTo) {
            if (!$colorTo) {
                $cntFreeTo ++;
                continue;
            }
            return $colorFrom === $colorTo && $cntFreeTo >= $cntFrom;
        }
        if (4 == $cntFreeTo && $cntTotalFrom == $cntFrom) {
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

    private function move($bootles, $from, $to)
    {
        $colorMove  = "";
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

    private function nextStep($bootles, $step, $hashParent, $from = null, $to = null)
    {
        if ($this->solved) {
            return;
        }
        if ($step > 50000) {
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
                $wsRow = $this->selectRow("ws", ['level' => $this->level, 'hash'  => $hash, 'from' => $keyFrom, 'to' => $keyTo]);
                if ($wsRow) {
                    // уже был переход на в такую позицию
                    continue;
                }
                $row = [
                    'level' => $this->level,
                    'step'  => $step,
                    'from' => $keyFrom,
                    'to'   => $keyTo,
                    'hash'  => $hash,
                    'hash_parent'  => $hashParent,
                    'solved' => $this->checkSolved($_bootles),
                    'bootles' => $bootlesJson
                ];
                $this->insertOrUpdateRow("ws", $row, ['level', 'step', 'hash', 'hash_parent']);
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


    public function setBootles($bootles)
    {
        $this->bootles = $bootles;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }

    private $level = 0;

    private $bootles = [];

    private $solved = false;

}

