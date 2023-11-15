<?php

require_once(MOD_DIR . "main.php");

require_once(INC_DIR . "WsClass.php");

set_time_limit(60);

class ws extends main
{

    public function show($params = [])
    {
        $this->data = [
            'level'   => empty($_REQUEST['level']) ? 0 : $_REQUEST['level'],
            'bootles' => empty($_REQUEST['bootles']) ? 11 : $_REQUEST['bootles']
        ];
        switch ($this->data['bootles']) {
            case 11:
                $this->data['colors'] = [
                    'red', 'green', 'violet', '#00007f', '#3f3fff',
                    'yellow', 'orange', '#606000', '#909000', '#007f7f',
                    '#9f009f'
                ];
                break;
        }
    }

    public function solve($params = [])
    {
        header("Content-type: application/json");
        try {
            $ws = new WsClass($params['level'], json_decode($params['bootles'], true));
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