<?php

use app\inc\DeepState;

require_once(INC_DIR . "DeepState.php");
require_once(MOD_DIR . "main.php");

set_time_limit(0);

class ds extends main
{

    public function __construct()
    {
        $this->deepState = new DeepState();
        return parent::__construct();
    }

    public function delta($params = [])
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
        $this->show();
    }

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
        $this->show();
    }

    /**
     *
     * {@inheritDoc}
     * @see main::show()
     */
    public function show($params = [])
    {
        $this->data["attrs"] = $this->deepState->selectRows("ds_attr");
        $detailRows = $this->deepState->queryRows("
SELECT
    EXTRACT(YEAR FROM \"date\") AS year,
    ds_attr_id,
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

    public function year($params = [])
    {
        $this->data["params"]   = $params;
        $this->data["statRows"] = $this->deepState->queryRows("
SELECT
    EXTRACT(MONTH FROM \"date\") AS month,
    SUM(delta) AS delta
  FROM ds_stat
 WHERE EXTRACT(YEAR FROM \"date\") = {$params["year"]}
   AND ds_attr_id = {$params["attr_id"]}
 GROUP BY 1
 ORDER BY 1");
    }

    private $deepState;

}