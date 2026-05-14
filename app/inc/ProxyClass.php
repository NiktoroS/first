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

    private $pipes = [];
    private $procs = [];

    public function __construct()
    {
        global $logs;
        if (empty($logs)) {
            $this->logs = new Logs("Proxy");
        } else {
            $this->logs = &$logs;
        }
        $this->pgSql = new PgSqlStorage();
    }

    public function check($procs = 10)
    {
        for ($proc = 0; $proc < $procs; $proc ++) {
            $descriptorspec = [
                0 => ["pipe", "r"], // stdin
                1 => ["pipe", "w"], // stdout
                2 => ["pipe", "w"] // stderr
            ];
            $this->procs[$proc] = proc_open(
                "php " . APP_DIR . "robot" . DS . "proxyCheckProc.php {$procs} {$proc}",
                $descriptorspec,
                $this->pipes[$proc]
            );
            fclose($this->pipes[$proc][0]);
            $this->logs->add("proc_open: {$proc}");
        }
        foreach ($this->procs as $proc => $_proc) {
            $stream = "";
            while ($buf = fgets($this->pipes[$proc][1])) {
                $stream .= $buf;
            }
            $status = proc_get_status($_proc);
            if (!$status["running"]) {
                fclose($this->pipes[$proc][1]);
                fclose($this->pipes[$proc][2]);
                proc_close($_proc);
                $this->logs->add("proc_close: {$proc}");
            }
            if (!$stream) {
                continue;
            }
            $this->logs->add("{$proc}: {$stream}");
        }
    }

    public function getProxy($procs = 1, $proc = 0, $forCheck = false)
    {
        if (getenv("PROXY") && getenv("EXTERNAL_IP") != gethostbyname(gethostname()) ) {
            var_dump(gethostbyname(gethostname()));
            return $this->pgSql->selectRow($this->table, ["name" => base64_encode(getenv("PROXY"))]);
        }
//        return $this->pgSql->selectRow($this->table, ["id" => 59171]);
        $conditions = ["id % {$procs} = {$proc}"];
        $orderBy = "
success >= fail DESC,
deleted ASC,
avg ASC,
RANDOM()";
        if ($forCheck) {
            $conditions[]   = "fail < 10";
            $conditions[]   = "success < 5";
            $conditions[]   = "(fail = 0 AND success = 0 OR updated_at < '" . date("Y-m-d 00:00:00") . "')";
            $orderBy        = "fail ASC, deleted ASC, updated_at ASC";
        } else {
            $conditions[]   = "(deleted = false OR success > 0)";
        }
        return $this->pgSql->selectRow($this->table, $conditions, $orderBy);
    }

    public function import()
    {
        if (getenv("PROXY")) {
            $this->insertOrUpdate(getenv("PROXY"), "", getenv("PROXY_AUTH"));
        }
        $this->pgSql->query("UPDATE {$this->table} SET deleted = true WHERE deleted = false AND success = 0");
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

    /**
     *
     * @param array $proxyRow
     */
    public function saveFail($proxyRow, $microtime = 5)
    {
        if ($proxyRow["success"]) {
            $proxyRow["avg"] = ($proxyRow["avg"] * $proxyRow["success"] + $microtime) / ($proxyRow["success"] + 1);
        }
        $proxyRow["fail"] ++;
        $this->updateProxy($proxyRow);
    }

    /**
     *
     * @param array $proxyRow
     * @param string $microtime
     */
    public function saveSuccess($proxyRow, $microtime = 5)
    {
        $proxyRow["success"] ++;
        $proxyRow["avg"] = ($proxyRow["avg"] * ($proxyRow["success"] - 1) + $microtime) / $proxyRow["success"];
        $this->updateProxy($proxyRow);
    }

    /**
     *
     * @param string $name
     * @param string $type
     * @param string $auth
     */
    private function insertOrUpdate($name, $type = "", $auth = "")
    {
        $name = trim($name);
        if ($type) {
            $name = "{$type}://{$name}";
        }
        $proxyRow = $this->pgSql->selectRow($this->table, ["name" => base64_encode($name)]);
        if ($proxyRow) {
            $proxyRow["deletad"]    = false;
            $proxyRow["auth"]       = base64_encode($auth);
            $proxyRow["updated_at"] = "now";
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

    /**
     *
     * @param array $proxyRow
     */
    private function updateProxy($proxyRow = [])
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