<?php

use app\inc\DeepState;

require_once(INC_DIR . "DeepState.php");
require_once("main.php");

set_time_limit(0);

class ds extends main
{

    public function __construct()
    {
        $this->deepState = new DeepState();
        return parent::__construct();
    }

    /**
     *
     * @param array $params
     */
    public function month($params = [])
    {
        $this->data["params"]   = $params;
        $this->data["statRows"] = $this->deepState->queryRows("
SELECT
    EXTRACT(day FROM \"date\") AS day,
    SUM(delta) AS delta
  FROM ds_stat
 WHERE ds_attr_id = {$params["attr_id"]}
   AND EXTRACT(MONTH FROM \"date\") = {$params["month"]}
   AND EXTRACT(YEAR FROM \"date\") = {$params["year"]}
 GROUP BY 1
 ORDER BY 1");
    }

    public function save($params = [])
    {
        $this->deepState->saveFile($_FILES["file"]["tmp_name"]);
        $this->delta();
        ToolsClass::location("/ds");
    }

    /**
     *
     * {@inheritDoc}
     * @see main::show()
     */
    public function show($params = [])
    {
        $this->data["attrs"] = $this->deepState->selectRows("ds_attr");
        usort(
            $this->data["attrs"], function($row1, $row2) {
                return convert_uudecode($row1["name"]) > convert_uudecode($row2["name"]);
            }
        );
        $detailRows = $this->deepState->queryRows("
SELECT
    ds_attr_id,
    EXTRACT(YEAR FROM \"date\") AS year,
    SUM(delta) AS delta
  FROM ds_stat
 GROUP BY 1, 2");
        foreach ($this->data["attrs"] as &$attr) {
            $attr["stat"] = $this->deepState->selectRow("ds_stat", ["ds_attr_id" => $attr["id"]], "\"date\" DESC");
            $attr["stat"]["detail"] = [];
            foreach ($detailRows as $detailRow) {
                if ($detailRow["ds_attr_id"] == $attr["id"]) {
                    $attr["stat"]["detail"][$detailRow["year"]] = $detailRow["delta"];
                }
            }
        }
    }

    /**
     *
     * @param array $params
     */
    public function year($params = [])
    {
        $this->data["params"]   = $params;
        $this->data["statRows"] = $this->deepState->queryRows("
SELECT
    EXTRACT(MONTH FROM \"date\") AS month,
    SUM(delta) AS delta
  FROM ds_stat
 WHERE ds_attr_id = {$params["attr_id"]}
   AND EXTRACT(YEAR FROM \"date\") = {$params["year"]}
 GROUP BY 1
 ORDER BY 1");
    }

    private $deepState;

    private function delta()
    {
        foreach ($this->deepState->selectRows("ds_attr") as $attr) {
            $total = 0;
            foreach ($this->deepState->selectRows("ds_stat", ["ds_attr_id" => $attr["id"]], "\"date\" ASC") as $stat) {
                if (!$stat["delta"] && intval($stat["total"]) <> $total) {
                    $this->deepState->updateRow("ds_stat", ["id" => $stat["id"], "delta" => intval($stat["total"]) - $total]);
                }
                $total = intval($stat["total"]);
            }
        }
    }
}