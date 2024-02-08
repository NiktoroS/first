<?php

require_once(MOD_DIR . "main.php");

require_once(INC_DIR . "WsClass.php");

set_time_limit(0);

class ws extends main
{

    public function show($params = [])
    {
        $this->data = [
            'bootles' => empty($_REQUEST['bootles']) ? 15 : $_REQUEST['bootles'],
            'bootleRows' => [],
            'colors'  => [],
            'colorCode'   => empty($_REQUEST['colorCode']) ? false : true,
            'level'   => empty($_REQUEST['level']) ? 0 : $_REQUEST['level']
        ];
        $ws = new WsClass();
        $ws->setLevel($this->data['level']);
        $wsLevelRow = $ws->selectRow('ws_levels', ['level' => $this->data['level']]);
        if ($wsLevelRow) {
            $this->data['bootleRows'] = json_decode($wsLevelRow['bootles'], true);
        }
        switch ($this->data['bootles']) {
            case 11:
                $this->data['newLines'] = [5, 10];
                break;

            case 13:
            case 15:
                $this->data['newLines'] = [6, 12];
                break;
        }
        if ($_FILES && $_FILES['file'] && $_FILES['file']["tmp_name"]) {
            $im = imagecreatefrompng($_FILES['file']["tmp_name"]);
        } else {
            $im = imagecreatefrompng(ROOT_DIR . "/content//Screenshot_{$this->data['bootles']}.png");
        }
        if ($im) {
            $this->data['rgbRows'] = [];

            $rgb = imagecolorat($im, 10, 15);

            for ($y = 0; $y < imagesy($im); $y ++) {
                for ($x = 0; $x < imagesx($im); $x ++) {
                    $rgb = sprintf("#%06X", imagecolorat($im, $x, $y));
                    if (empty($this->data['rgbRows'][$rgb])) {
                        $this->data['rgbRows'][$rgb] = 0;
                    }
                    $this->data['rgbRows'][$rgb] ++;
                }
            }
            foreach ($this->data['rgbRows'] as $color => $cnt) {
                if ($cnt < 20000) {
                    unset($this->data['rgbRows'][$color]);
                }
                if ($cnt > 30000) {
                    unset($this->data['rgbRows'][$color]);
                }
                if ("#C1C1C1" == $color) {
                    unset($this->data['rgbRows'][$color]);
                }
            }
            $this->data['colors'] = array_keys($this->data['rgbRows']);
        }
    }

    public function solve($params = [])
    {
        header("Content-type: application/json");
        try {
            $ws = new WsClass();
            $ws->setBootles(json_decode($params['bootles'], true));
            $ws->setLevel($params['level']);
            echo (json_encode($ws->solve()));
        } catch (Exception $e) {
            echo (json_encode([
                'error' => $e->getMessage(),
                'params' => $params,
           ]));
        }
        exit;
    }

}