<?php
namespace app\inc;

use app\inc\Storage\PgSqlStorage;

require_once(INC_DIR . "Logs.php");
require_once(INC_DIR . "Storage/PgSqlStorage.php");

/**
 * Telegram
 *
 * @author Andrey A. Sirotkin <first@mail.ru>
 * @since  20.12.2016
 */

class ProxyClass
{

    private $dir = ROOT_DIR . "userdata" . DS . "PROXY-List" . DS;
    private $logs;
    private $pgSql;
    private $table = "tg_addresses";

    public function __construct()
    {
        $this->logs  = new Logs("Proxy");
        $this->pgSql = new PgSqlStorage();
    }

    public function getProxy()
    {
        if (getenv("PROXY") && getenv("EXTERNAL_IP") != gethostbyname(gethostname()) ) {
            return $this->pgSql->selectRow($this->table, ["name" => base64_encode(getenv("PROXY"))]);
        }
        return $this->pgSql->selectRow($this->table, ["deleted" => false], "success >= fail DESC, avg ASC, RANDOM()");
    }

    public function import()
    {
        $this->pgSql->execute("UPDATE {$this->table} SET deleted = true");
        if (getenv("PROXY")) {
            $this->insertOrUpdate(getenv("PROXY"), "", getenv("PROXY_AUTH"));
        }
        foreach (scandir($this->dir) as $file) {
            if (!preg_match('/\.txt$/', $file)) {
                continue;
            }
            $type = "";
            switch ($file) {
                case "socks4.txt":
                    $type = "socks4";
                    break;
                case "socks5.txt":
                    $type = "socks5";
                    break;
            }
            foreach (file($this->dir . $file) as $str) {
                $this->insertOrUpdate($str, $type);
            }
        }
    }

    public function saveFail($proxyRow)
    {
        $proxyRow["fail"] ++;
        $this->updateProxy($proxyRow);
    }

    public function saveSuccess($proxyRow, $microtime)
    {
        $proxyRow["success"] ++;
        $proxyRow["avg"] = ($proxyRow["avg"] * ($proxyRow["success"] - 1) + $microtime) / $proxyRow["success"];
        $this->updateProxy($proxyRow);
    }

    private function insertOrUpdate($name, $type = "", $auth = "")
    {
        $name = trim($name);
        if ($type) {
            $name = "{$type}://{$name}";
        }
        $proxyRow = $this->pgSql->selectRow($this->table, ["name" => base64_encode($name)]);
        if ($proxyRow) {
            $proxyRow["deletad"] = false;
            $proxyRow["auth"]    = base64_encode($auth);
            $this->updateProxy($proxyRow);
        } else {
            $this->pgSql->insertRow(
                $this->table,
                [
                    "name" => base64_encode($name),
                    "auth" => base64_encode($auth),
                    "type" => $type,
                    "created_at" => "now",
                    "updated_at" => "now",
                ]
            );
        }
    }

    private function updateProxy($proxyRow)
    {
        $proxyRow["updated_at"] = "now";
        $this->pgSql->updateRow($this->table, $proxyRow);
    }
}



/**
CREATE TABLE public.tg_addresses (
	id bigserial NOT NULL,
	created_at timestamp NOT NULL,
	updated_at timestamp NOT NULL,
	deleted bool NOT NULL DEFAULT false,
	"name" varchar NOT NULL,
	auth varchar NULL,
	"type" varchar NULL,
	avg numeric NULL,
	fail int4 NOT NULL DEFAULT 0,
	success int4 NOT NULL DEFAULT 0,
	CONSTRAINT tg_addresses_pk PRIMARY KEY (id)
);

*/