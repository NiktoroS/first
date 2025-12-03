<?php

use app\inc\WaterSolver;

require_once(MOD_DIR . "main.php");
require_once(INC_DIR . "WaterSolver.php");

set_time_limit(0);

class ws extends main
{

    public function saveAcc($params = [])
    {
        $ws = new WaterSolver();
        $ws->setLevel($params["level"]);
        $filename = $ws->saveAcc() . ".txt";
        $filepath = TMP_DIR . $filename;
        header("Content-Description: File Transfer");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header("Content-Transfer-Encoding: binary");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: public");
        header("Content-Length: " . filesize($filepath));
        echo(file_get_contents($filepath));
    }

    /**
     *
     * @param array $params
     */
    public function solve($params = [])
    {
        header("Content-type: application/json");
        try {
            $ws = new WaterSolver();
            $ws->setBottles(json_decode($params["bottles"], true));
            $ws->setLevel($params["level"]);
            $result = $ws->solve(
                !empty($params["resolve"]),
                !empty($params["saveToDb"]),
                !empty($params["reverse"])
            );
            if ($result["success"]) {
                $ws->saveAcc($result);
            }
            echo (json_encode($result));
        } catch (Exception $e) {
            echo (json_encode([
                'error' => $e->getMessage(),
                'params' => $params,
            ]));
        }
        exit;
    }

    /**
     *
     * {@inheritDoc}
     * @see main::show()
     */
    public function show($params = [])
    {
        $ws = new WaterSolver();
        $cntColors  = empty($_REQUEST["cntColors"]) ? 15 : intval($_REQUEST["cntColors"]);
        $level      = !empty($_FILES["file"]["tmp_name"]) ? WaterSolver::getLevelFromFile($_FILES["file"]["tmp_name"]) : $_REQUEST["level"] ?? 0;
        $this->data = $ws->setData($cntColors, $level);
        if (!empty($_FILES["file"]["tmp_name"])) {
            $this->data["bottles"] = $ws->setBottlesFromFile($_FILES["file"]["tmp_name"], $this->data["colors"]);
        }
    }


}