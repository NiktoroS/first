<?php

ini_set("memory_limit", -1);

use app\inc\Storage\PgsqlStorage;

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
    public function solve($resolve = false)
    {
        $this->log->add("solve: {$this->level}");
        $result = [
            'success'   => true,
            'start'     => date("H:i:s")
        ];
        if ($colors = $this->checkColors()) {
            return [
                'success' => false,
                'colors' => $colors
            ];
        }
        $wsRow = $this->selectRow("ws", ['level' => $this->level, 'solved' => true]);
        if ($wsRow && !$resolve) {
            $result['moves'] = $this->selectRows("ws", ['level' => $this->level, "step > 0"], "step");
            return $this->result($result);
        }
        $this->query("DELETE FROM ws WHERE level = {$this->level}");
        $this->vacuum();
        $step   = 1;
        if ($this->bootles) {
            $bootlesJson    = json_encode($this->bootles);
        } else {
            $wsLevelsRow = $this->selectRow("ws_levels", ['level' => $this->level]);
            if (!$wsLevelsRow) {
                return $this->result($result);
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
            'level' => $this->level,
            'solved'    => false,
            'step'  => 0,
            'to'    => 0
        ];
        $this->insertWsRow($row);
        $this->nextStep($this->bootles, $step, $hash);
        $this->insertWsRows();
        if (!$this->solved) {
            $result['success'] = false;
            return $this->result($result);
        }
        $wsRow = $this->selectRow("ws", ['solved' => true, 'level' => $this->level], "", "step");
        $this->query("DELETE FROM ws WHERE level = {$wsRow['level']} AND step > {$wsRow['step']}");
        while ($wsRow['step'] > 0) {
            $step = $wsRow['step'];
            $this->query("DELETE FROM ws WHERE level = {$wsRow['level']} AND step = {$wsRow['step']} AND hash <> '{$wsRow['hash']}'");
            $wsRow = $this->selectRow("ws", ['hash' => $wsRow['hash_parent'], 'level' => $wsRow['level'], "step < {$wsRow['step']}"]);
            $this->query("DELETE FROM ws WHERE level = {$wsRow['level']} AND step > {$wsRow['step']} AND step < {$step}");
        }
        $result['moves'] = $this->selectRows("ws", ['level' => $this->level, "step > 0"], "step");
        return $this->result($result);
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
        foreach ($bootleFrom as $colorFrom) {
            if ($colorFrom) {
                break;
            }
        }
        if (!$bootleTo[3] && $bootleFrom[3] == $colorFrom) {
            // не имеет смысла перемещать "всё" в "пустоту"
            return false;
        }
        foreach ($bootleTo as $colorTo) {
            if ($colorTo) {
                return $colorFrom == $colorTo;
            }
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
        if ($cnt < $this->hasheCntChunk) {
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
        $this->vacuum();
        $this->useDb = true;
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
        $color = "";
        foreach ($bootles[$from] as $keyFrom => $colorFrom) {
            if (!$colorFrom) {
                continue;
            }
            if (!$color) {
                $color = $colorFrom;
            }
            if ($color != $colorFrom) {
                break;
            }
            foreach ($bootles[$to] as $keyTo => $colorTo) {
                if ($colorTo) {
                    break 2;
                }
                if (!isset($bootles[$to][$keyTo + 1]) || $bootles[$to][$keyTo + 1] == $color) {
                    $bootles[$to][$keyTo] = $color;
                    $bootles[$from][$keyFrom] = "";
                    break;
                }
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
/**/
        $keys = array_keys($bootles);
        shuffle($keys);
        foreach ($keys as $keyFrom) {
            $bootleFrom = $bootles[$keyFrom];
            foreach ($keys as $keyTo) {
                $bootleTo = $bootles[$keyTo];
/**
        foreach ($bootles as $keyFrom => $bootleFrom) {
            foreach ($bootles as $keyTo => $bootleTo) {
/***/
                if ($keyFrom == $keyTo) {
                    continue;
                }
                if ($keyFrom == $prevTo && $keyTo == $prevFrom) {
//                    continue;
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
//                 if ($wsRow) {
//                     // уже был такой переход с данной позиции
//                     continue;
//                 }
                $_bootles = $this->move($bootles, $keyFrom, $keyTo);
                $this->insertWsRow([
                    'from'  => $keyFrom,
                    'hash'  => $hash,
                    'hash_parent'   => $hashParent,
                    'level' => $this->level,
                    'solved'    => $this->checkSolved($_bootles),
                    'step'  => $step,
                    'to'    => $keyTo
                ]);
                if ($this->solved) {
                    return;
                }
                $this->nextStep($_bootles, $step + 1, $hash, $keyFrom, $keyTo);
            }
        }
    }

    /**
     *
     * @param array $result
     * @return array
     */
    private function result($result)
    {
        $result['finish'] = date("H:i:s");
        return $result;
    }

    /**
     *
     * @param string $hash
     * @return array
     */
    private function selectWsRow($hash = "")
    {
        if (empty($this->hashes[$hash])) {
            if (!$this->useDb) {
                return [];
            }
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

    private function vacuum()
    {
        $this->query("vacuum full verbose analyse ws");
        $this->log->add("vacuum done:");
    }

    private $bootles = [];

    private $hashes  = [];

    private $hasheCnt     = 0;

    private $hasheCntChunk = 16777216;

    private $level     = 0;

    private $solved = false;

    private $useDb = false;
}

