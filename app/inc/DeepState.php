<?php

namespace app\inc;

use app\inc\Storage\MySqlStorage;
use app\inc\Storage\PgSqlStorage;

define('MAX_FILE_SIZE', 10000000);
ini_set("memory_limit", "4095M");
set_time_limit(0);

require_once("simple_html_dom.php");
require_once("Storage/MySqlStorage.php");
require_once("Storage/PgSqlStorage.php");

class DeepState extends PgSqlStorage
{

    public $db;

    public function __construct()
    {
        if (getenv("DS_DB") && "PG" == getenv("DS_DB")) {
            $this->db = new PgSqlStorage();
        } else {
            $this->db = new MySqlStorage();
        }
    }
    /**
     *
     * @param string $file
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
                $this->db->insertOrUpdateRows("ds_stat", $statRows, ["date", "ds_attr_id"]);
            }
        } catch (\Exception $exception) {
            $this->db->logs->add($exception);
        }
    }

    public function updateAttrRows()
    {
        foreach ($this->db->selectRows("ds_attr") as $attrRow) {
            $attrRow["name"]        = convert_uuencode($attrRow["name"]);
            $attrRow["updated_at"]  = "NOW()";
            $this->db->updateRow("ds_attr", $attrRow);
        }
    }

    /**
     *
     * @param string $name
     * @return array
     * @throws
     */
    private function getAttrRow($name = "")
    {
        $attrRow = $this->db->selectRow("ds_attr", ["name" => $name]);
        if ($attrRow) {
            return $attrRow;
        }
        $this->db->insertRow("ds_attr", ["name" => $name, "created_at" => "NOW()"]);
        return $this->getAttrRow($name);
    }
}

/**

-- PgSql

CREATE TABLE ds_attr (
    id bigserial NOT NULL,
    "name" varchar(255) NOT NULL,
    description text NULL,
    created_at timestamp(0) NULL,
    updated_at timestamp(0) NULL,
    hidden bool NOT NULL DEFAULT false,
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

-- MySql

CREATE TABLE ds_attr (
    id BIGINT AUTO_INCREMENT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    hidden TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ds_stat (
    id BIGINT AUTO_INCREMENT NOT NULL,
    ds_attr_id BIGINT NOT NULL,
    `date` DATE NOT NULL,
    total INT NULL,
    delta INT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY ds_stat_unique (`date`, ds_attr_id),
    CONSTRAINT ds_stat_ds_attr_fk FOREIGN KEY (ds_attr_id) REFERENCES ds_attr(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

*/