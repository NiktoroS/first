<?php
/**
 *
 * @author <first@mail.ru>
 * @since  2017-03-13
 */

require_once MOD_DIR . "main.php";

class electro extends main
{

    public function show($params = array ())
    {
        if (isset($_REQUEST["time"]) and !empty($_REQUEST["value"])) {
            $request = $_REQUEST;
#            $request["value"] *= 0.1;
            $this->storage->insertOrUpdateRow("electro", $request);
            header("HTTP/1.1 301");
            header("Location: /electro");
            exit;
        }
        $this->data = array (
            "rows" => $this->storage->selectRows("electro", array ("active" => 1, "time >= '2017-05-01'"), "time ASC")
        );
        foreach ($this->data["rows"] as $key => $row) {
            $row["delta"] = 0;
            if ($key) {
                $row["delta"] = round(100 * 3600 * 24 * ($row["value"] - $this->data["rows"][$key - 1]["value"]) / (strtotime($row["time"]) - strtotime($this->data["rows"][$key - 1]["time"])));
            }
            $this->data["rows"][$key] = $row;
        }
    }

}
