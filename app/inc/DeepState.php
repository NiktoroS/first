<?php

namespace app\inc;

use app\inc\Storage\PgsqlStorage;

set_time_limit(0);
ini_set("memory_limit", "20047M");
define('MAX_FILE_SIZE', 10000000);

require_once(INC_DIR . "simple_html_dom.php");
require_once(INC_DIR . "Storage/PgSqlStorage.php");

class DeepState extends PgSqlStorage
{

    /**
     *
     * @param string $file
     * @return number
     */
    public function saveFile(string $file)
    {
        try {
            $dom = file_get_html($file);
            foreach ($dom->find("li.gold") as $liDay) {
                $spanDay = $liDay->find("span[class='black']", 0);
                if (!$spanDay || !preg_match('/\d{2}\.\d{2}\.\d{4}/', $spanDay->plaintext)) {
                    continue;
                }
                $date = date("Y-m-d", strtotime($spanDay->plaintext));
                $ul = $liDay->find("ul", 0);
                $statRows = [];
                foreach ($ul->find("li") as $liPosition) {
                    $row = explode("&nbsp;—", $liPosition->plaintext);
                    if (empty($row[1])) {
                        $row = explode("&nbsp;&mdash;", $liPosition->plaintext);
                    }
                    list($name, $stat) = array_map("trim", $row);
                    $arttrRow   = $this->getAttrRow(convert_uuencode($name));
                    $small      = $liPosition->find("small", 0);
                    $statRows[] = [
                        "date"          => $date,
                        "delta"         => $small ? preg_replace('/\(|\)/', "", $small->plaintext) : "NULL",
                        "ds_attr_id"    => $arttrRow["id"],
                        "total"         => intval(preg_replace('/^\D+/', "", $stat)),
                        "updated_at"    => "NOW()"
                    ];
                }
                $this->insertOrUpdateRows("ds_stat", $statRows, ["date", "ds_attr_id"]);
            }
        } catch (\Exception $exception) {
            $this->logs->add($exception);
        }
    }

    public function updateAttrRows()
    {
        foreach ($this->selectRows("ds_attr") as $attrRow) {
            $attrRow["name"]        = convert_uuencode($attrRow["name"]);
            $attrRow["updated_at"]  = "NOW()";
            $this->updateRow("ds_attr", $attrRow);
        }
    }

    private function getAttrRow($name = "")
    {
        $attrRow = $this->selectRow("ds_attr", ["name" => $name]);
        if ($attrRow) {
            return $attrRow;
        }
        $this->insertRow("ds_attr", ["name" => $name, "created_at" => "NOW()"]);
        return $this->getAttrRow($name);
    }
}

/**

CREATE TABLE ds_attr (
    id bigserial NOT NULL,
    "name" varchar(255) NOT NULL,
    description text NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT ds_attr_pkey PRIMARY KEY (id)
);

CREATE TABLE ds_stat (
    id bigserial NOT NULL,
    ds_attr_id int8 NOT NULL,
    "date" date NOT NULL,
    total int4 NULL,
    delta int4 NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    CONSTRAINT ds_stat_pkey PRIMARY KEY (id),
    CONSTRAINT ds_stat_unique UNIQUE (date, ds_attr_id),
    CONSTRAINT ds_stat_ds_attr_fk FOREIGN KEY (ds_attr_id) REFERENCES ds_attr(id)
);
*/
