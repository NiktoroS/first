<?php
/**
 * rbc.ru
 * @author <first@mail.ru>
 * @since  2018-06-22
 */

require_once(MOD_DIR . "main.php");

class rbc extends main
{

    public function show($params = array ())
    {
        $this->data = array (
            "all" => array (
                "rows" => $this->storage->queryRows(
                    "SELECT DATE_FORMAT(created, '%Y-%m-%d') AS `date`, AVG(USD) AS USD, AVG(brand) AS brand" .
                    "  FROM rbc" .
                    " WHERE USD <> 0" .
                    "   AND brand <> 0" .
                    " GROUP BY 1" .
                    " ORDER BY 1 ASC"
                ),
                "min" => 20,
                "divide" => 2500,
            ),
            "last" => array (
                "rows" => array_reverse(
                    $this->storage->queryRows(
                        "SELECT DATE_FORMAT(created, '%Y-%m-%d') AS `date`, AVG(USD) AS USD, AVG(brand) AS brand" .
                        "  FROM rbc" .
                        " WHERE USD <> 0" .
                        "   AND brand <> 0" .
                        " GROUP BY 1" .
                        " ORDER BY 1 DESC" .
                        " LIMIT 500"
                    )
                ),
                "min" => 35,
                "divide" => 3000,
            ),
            "cur" =>  array (
                "rows" => array_reverse(
                    $this->storage->queryRows(
                        "SELECT DATE_FORMAT(created, '%Y-%m-%d %H:30:00') AS `date`, AVG(USD) AS USD, AVG(brand) AS brand" .
                        "  FROM rbc" .
                        " WHERE USD <> 0" .
                        "   AND brand <> 0" .
                        " GROUP BY 1" .
                        " ORDER BY 1 DESC" .
                        " LIMIT 500"
                    )
                ),
                "min" => 45,
                "divide" => 0,
            ),
        );
        $this->data["cur"]["divide"] = empty($_REQUEST["divide"]) ? round($this->data["cur"]["rows"][count($this->data["cur"]["rows"]) - 1]["USD"] * $this->data["cur"]["rows"][count($this->data["cur"]["rows"]) - 1]["brand"] / 50) * 50 : $_REQUEST["divide"];
    }
}