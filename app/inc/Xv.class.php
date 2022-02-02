<?php
/**
 * @category Library
 * @package  vx driver
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    2017-01-31
 */


require_once(INC_DIR . "Storage/MySqlStorage.php");
require_once(INC_DIR . "Tools.class.php");
require_once(INC_DIR . "Browser.class.php");

require(CNF_DIR . "xv.php");

class xvClass extends MySqlStorage
{

    public $browser, $site, $proxy;

    public function __construct($dsnMySql = [])
    {
        global $site, $proxy;
        $this->site  = $site;
        $this->proxy = $proxy;
        $this->browser = new Browser($this->proxy);
        if (!$dsnMySql) {
            global $dsnMySql;
        }
        parent::__construct($dsnMySql);
    }

    /**
     * @throws
     */
    public function tags()
    {
        $htmlObj = $this->requestHtml("/tags");
        if (!$htmlObj) {
            throw new Exception("empty : {$this->site}/tags");
        }
        $ulObj = $htmlObj->find("ul.tags-list", 1);
        if (!$ulObj) {
            throw new Exception("not found <ul class=\"tags-list\"> : {$this->site}/tags");
        }
        $return = 0;
        foreach ($ulObj->find("li") as $liObj) {
            $return ++;
            $aObj = $liObj->find("a", 0);
            $href = str_replace(" ", "-", $aObj->href);
            $tagRow  = $this->selectRow("tag", ["name_tag" => $href]);
            if (!$tagRow) {
                $bObj = $aObj->find("b", 0);
                $tagRow = array (
                    "name"     => trim($bObj->plaintext),
                    "name_tag" => $href,
                );
            }
            $spanObj = $aObj->find("span", 0);
            $tagRow["cnt"] = str_replace(",", "", trim($spanObj->plaintext));
            $tagRow["id"]  = $this->insertOrUpdateRow("tag", $tagRow);
        }
        return $return;
    }

    public function parceTag(&$tagRow)
    {
        $vtRows    = [];
        $videoRows = [];
        $href = $tagRow["name_tag"];
        while ($href) {
            $htmlObj = $this->requestHtml($href);
            if (!$htmlObj) {
                break;
            }
            $divListObj  = $htmlObj->find("div.mozaique", 0);
            if ($divListObj) {
                foreach ($divListObj->find("div.thumb-block") as $divObj) {
                    if (!preg_match('/\_/', $divObj->id)) {
                        continue;
                    }
                    list($table, $id) = explode("_", $divObj->id);
                    if ("video" != $table)  {
                        continue;
                    }
                    $vtRows[] = [
                        "id_video" => $id,
                        "id_tag"   => $tagRow["id"],
                        "active"   => 1
                    ];
                    $videoExistRow = $this->selectRow($table, ["id" => $id]);
                    if (!$videoExistRow or in_array($videoExistRow["name_tag"], array ("/verified/videos", "/verified-men/videos"))) {
                        $videoRows[] = $this->parceDiv($divObj, $id);
                    }
                }
            }
            $href = $this->nextPageTag($htmlObj);
        }
        if ($videoRows) {
            $this->insertOrUpdateRows("video", $videoRows);
            $this->logs->add($this->site . $tagRow["name_tag"] . " : " . count($videoRows));
        }
        if ($vtRows) {
            $this->insertOrUpdateRows("video_tag", $vtRows);
        }
        return count($videoRows);
    }

    public function parceHref($href = "/profiles-index")
    {
        $new = 0;
        while ($href) {
            $cnt = 0;
            $htmlObj = $this->requestHtml($href);
            if (!$htmlObj) {
                break;
            }
            $divListObj  = $htmlObj->find("div.mozaique", 0);
            if ($divListObj) {
                foreach ($divListObj->find("div.thumb-block") as $divObj) {
                    $pObj = $divObj->find("p.profile-name", 0);
                    if ($pObj) {
                        $aObj = $pObj->find("a", 0);
                        if ($aObj) {
                            $profileRow = $this->selectRow("profile", ["name_tag" => trim($aObj->href)]);
                            if (!$profileRow) {
                                $profileRow = [
                                    "name"     => trim($aObj->plaintext),
                                    "name_tag" => trim($aObj->href),
                                ];
                                $profileRow["id"] = $this->insertRow("profile", $profileRow);
                                $cnt += $this->parceProfile($profileRow);
                            }
#                            $new += $this->parceProfile($profileRow);
                        }
                    }
                }
            }
            if ($cnt) {
                $this->logs->add($this->site . $href . " : " . $cnt);
                $new += $cnt;
            }
            $href = $this->nextPageTag($htmlObj);
        }
        return $new;
    }

    public function parceProfile(&$profileRow)
    {
        $new  = 0;
        $type = 0;
        $href = $profileRow["name_tag"] . "/videos/best";
        $htmlObj = $this->requestHtml($href);
        $profileRow["cnt_upload"] = 0;
        $profileRow["cnt_video"]  = 0;
        $divObj  = $htmlObj->find("div[id='profile-listing-uploads']", 0);
        if ($divObj) {
            $type += 1;
            $new += $this->parceProfileVideo($profileRow, "best");
        }
        $divObj  = $htmlObj->find("div[id='profile-listing-pornstar']", 0);
        if ($divObj) {
            $type += 2;
            $new += $this->parceProfileVideo($profileRow, "pornstar");
        }
        if ($new) {
            $this->logs->add($this->site . $profileRow["name_tag"] . " : " . $new);
        }
        if ($new or $type != $profileRow["type"]) {
            $profileRow["type"] = $type;
            $this->updateRow("profile", $profileRow);
        }
        return $new;
    }

    public function parceProfileVideo(&$profileRow, $part = "best")
    {
        if ("best" == $part) {
            $idsVideo = $this->queryCol("SELECT id FROM video WHERE id_profile = " . $profileRow["id"]);
        }
        $href = $profileRow["name_tag"] . "/videos/" . $part . "/0";
        $videoRows = [];
        $vmRows    = [];
        while ($href) {
            $htmlObj = $this->requestHtml($href);
            if (!$htmlObj) {
                break;
            }
            $divListObj  = $htmlObj->find("div.mozaique", 0);
            if ($divListObj) {
                foreach ($divListObj->find("div.thumb-block") as $divObj) {
                    list($table, $id) = explode("_", $divObj->id);
                    if ("best" == $part) {
                        $profileRow["cnt_upload"] ++;
                        if (!in_array($id, $idsVideo)) {
                            $videoRows[] = $this->parceDiv($divObj, $id, array ("id_profile" => $profileRow["id"]));
                        }
                    } elseif ("pornstar" == $part) {
                        $profileRow["cnt_video"] ++;
                        if (!$this->selectRow($table, ["id" => $id])) {
                            $videoRows[] = $this->parceDiv($divObj, $id);
                        }
                        $vmRows[] = array (
                            "id_video" => $id,
                            "id_model" => $profileRow["id"],
                        );
                    }
                }
            }
            $href = $this->nextPageProfile($htmlObj);
            if ($href) {
                $href = $profileRow["name_tag"] . "/videos/" . $part . "/" . str_replace("#", "", $href);
            }
        }
        if ($videoRows) {
            $this->insertOrUpdateRows("video", $videoRows);
        }
        if ($vmRows) {
            $this->insertOrUpdateRows("video_model", $vmRows);
        }
        return count($videoRows);
    }

    public function parceNew($page)
    {
        $new = 0;
        $href = $page ? "/new/" . $page : "";
        $videoRows = array ();
        $htmlObj = $this->requestHtml($href);
        if (!$htmlObj) {
            return;
        }
        $divListObj  = $htmlObj->find("div.mozaique", 0);
        if ($divListObj) {
            foreach ($divListObj->find("div.thumb-block") as $divObj) {
                list($table, $id) = explode("_", $divObj->id);
                if (!$this->selectRow($table, ["id" => $id])) {
                    $videoRows[] = $this->parceDiv($divObj, $id);
                }
            }
        }
        if ($videoRows) {
            $this->insertOrUpdateRows("video", $videoRows);
            $this->logs->add($href . " : " . count($videoRows));
        }
    }

    public function parceBest($page)
    {
        $new = 0;
        $href = "/best/" . ($page ? $page : "");
        $videoRows = array ();
        $htmlObj = $this->requestHtml($href);
        if (!$htmlObj) {
            return;
        }
        $divListObj  = $htmlObj->find("div.mozaique", 0);
        if ($divListObj) {
            foreach ($divListObj->find("div.thumb-block") as $divObj) {
                list($table, $id) = explode("_", $divObj->id);
                if (!$this->selectRow($table, ["id" => $id])) {
                    $videoRows[] = $this->parceDiv($divObj, $id);
                }
            }
        }
        if ($videoRows) {
            $this->insertOrUpdateRows("video", $videoRows);
            $this->logs->add($href . " : " . count($videoRows));
        }
    }

    public function parceMc($href)
    {
        if (in_array($href, array ("/tags"))) {
            return;
        }
        $this->logs->add("href : " . $href);
        $videoRows = array ();
        while ($href) {
            $videoRows = array ();
            $htmlObj = $this->requestHtml($href);
            if (!$htmlObj) {
                break;
            }
            $divListObj  = $htmlObj->find("div.mozaique", 0);
            if ($divListObj) {
                foreach ($divListObj->find("div.thumb-block") as $divObj) {
                    list($table, $id) = explode("_", $divObj->id);
                    if (!$this->selectRow($table, ["id" => $id])) {
                        $videoRows[] = $this->parceDiv($divObj, $id);
                    }
                }
            }
            $href = $this->nextPageTag($htmlObj);
        }
        if ($videoRows) {
            $this->insertOrUpdateRows("video", $videoRows);
            $this->logs->add("new : " . count($videoRows));
        }
        return count($videoRows);
    }

    public function requestHtml($href = "/", $maxAtt = 3)
    {
        global $logs;
        $att = 0;
        do {
            $htmlObj = $this->browser->requestHtml($this->site . $href);
            if (!$htmlObj) {
                if (in_array("HTTP/1.1 404 Not Found", $this->browser->resHeader)) {
                    $logs->add("404 browser->requestHtml : " . $this->site . $href);
                    return false;
                }
                $att ++;
                sleep($att * 30);
            }
        } while (!$htmlObj and $att <= $maxAtt);
        if (!$htmlObj) {
            $logs->add("empty browser->requestHtml : " . $this->site . $href . "\nResult header: " . var_export($this->browser->resHeader, true));
            $exec = "sudo service tor stop";
            exec($exec);
            $logs->add("exec({$exec})");
            $exec = "sudo service tor start";
            exec($exec);
            $logs->add("exec({$exec})");
            exec($exec);
        }
        return $htmlObj;
    }

    public function getImgSrcHash($imgSrc)
    {
        return preg_replace('/\S+\/(\w+)\.\w+\.jpg$/', '$1', $imgSrc);
    }

    public function nameTag($href)
    {
        $hrefRows = explode("/", $href);
        return array_pop($hrefRows);
    }

    public function search(&$request)
    {
        $mt = microtime(true);

        $dsnMaster = $this->dsn;
#        $dsnMaster["host"] = "192.168.1.148";
        $dbMaster  = new MySqlStorage($dsnMaster);

        $conditions = array (
            "`v`.`active` = 1",
        );
        if (empty($request["hd"])) {
            $request["hd"] = 0;
        } else {
            $conditions[] = "`v`.`hd` = 1";
        }
        if (empty($request["download"])) {
            $conditions[] = "`v`.`download` = 0";
            $request["download"] = 0;
        } else {
            $request["download"] = 1;
        }
        if (empty($request["vt2"])) {
            $request["vt2"] = 0;
        }
        if (empty($request["offset"])) {
            $request["offset"] = 0;
        }
        if (!isset($request["useMaster"])) {
            $request["useMaster"] = false;
        }
        if (!isset($request["limit"])) {
            $request["limit"] = 50;
        }

        $idsTag    = array ();
        $sql = "SELECT `v`.* FROM `video` AS `v`";
        if (empty($request["search"])) {
            $request["search"] = "";
        } else {
            $nameRows  = explode(" ", trim(preg_replace('/\s+/', " ", $request["search"])));
            $keyJoin   = 0;
            foreach ($nameRows as $name) {
                $idTag = $dbMaster->queryInt("SELECT id FROM tag_custom WHERE name = '" . $dbMaster->escapeString($name) . "'");
                if (!$idTag) {
                    $idTag = $dbMaster->queryInt("SELECT id FROM tag WHERE name = '" . $dbMaster->escapeString($name) . "'");
                    if ($idTag) {
                        $request["useMaster"] = true;
                    } else {
                        continue;
                    }
                }
                $keyJoin ++;
                $sql .=
                    " JOIN `video_tag_custom` AS `vt" . $keyJoin . "` ON (`v`.`id` = `vt" . $keyJoin . "`.`id_video` AND " . $idTag . " = `vt" . $keyJoin . "`.`id_tag`" . ($request["vt2"] ? " AND 1 = `vt" . $keyJoin . "`.`vt2`" : "") . ")";
                $conditions[] = "`vt" . $keyJoin . "`.`id_tag` = " . $idTag;
                if ($request["vt2"]) {
                    $conditions[] = "`vt" . $keyJoin . "`.`vt2` = 1";
                }
                $idsTag[] = $idTag;
            }
        }

        $sql .=
            "  WHERE " . join(" AND ", $conditions) .
            "  ORDER BY `v`.`rate` DESC, `v`.`created` DESC" .
            "  LIMIT " . $request["limit"] .
            " OFFSET " . $request["offset"];

        if ($request["useMaster"] and $idsTag) {
            $mySql = $dbMaster;
            $mySql->query(
                "INSERT INTO tag_custom (id, name, name_tag, cnt, cnt_v, cnt_v2, active, created)" .
                "SELECT id, name, name_tag, cnt, cnt_v, cnt_v2, active, NOW()" .
                "  FROM tag" .
                " WHERE id IN (" . join(", ", $idsTag) . ")" .
                "    ON DUPLICATE KEY UPDATE name = VALUES(name), name_tag = VALUES(name_tag), cnt = VALUES(cnt), cnt_v = VALUES(cnt_v), cnt_v2 = VALUES(cnt_v2), active = VALUES(active)"
            );
            $mySql->query(
                "INSERT INTO video_tag_custom (id_video, id_tag, vt2, active, created)" .
                "SELECT id_video, id_tag, vt2, active, NOW()" .
                "  FROM video_tag" .
                " WHERE id_tag IN (" . join(", ", $idsTag) . ")" .
                "    ON DUPLICATE KEY UPDATE id_video = VALUES(id_video), id_tag = VALUES(id_tag), vt2 = VALUES(vt2), active = VALUES(active)"
            );
        } else {
            $mySql = $dbMaster;
        }

        return array (
            "site"    => $this->site,
            "sql"     => $sql,
            "rows"    => $mySql->queryRows($sql),
            "mt"      => microtime(true) - $mt,
            "request" => $request,
        );
    }

    public function updCustom()
    {
        $this->query(
            "INSERT INTO tag_custom (id, name, name_tag, cnt, cnt_v, cnt_v2, created)" .
            "SELECT tag.id, tag.name, tag.name_tag, tag.cnt, tag.cnt_v, tag.cnt_v2, NOW()" .
            "  FROM tag" .
            "  JOIN tag_custom ON tag.id = tag_custom.id" .
            " WHERE tag_custom.active = 1" .
            "    ON DUPLICATE KEY UPDATE name = VALUES(name), name_tag = VALUES(name_tag), cnt = VALUES(cnt), cnt_v = VALUES(cnt_v), cnt_v2 = VALUES(cnt_v2)"
        );
        $this->logs->add("tag_custom: " . $this->affectedRows());

        $this->query(
            "INSERT INTO video_tag_custom (id_tag, active, id_video, vt2, created)" .
            "SELECT tag_custom.id, tag_custom.active, video_tag.id_video, video_tag.vt2, NOW()" .
            "  FROM tag_custom" .
            "  JOIN video_tag ON tag_custom.id = video_tag.id_tag" .
            "    ON DUPLICATE KEY UPDATE id_video = VALUES(id_video), id_tag = VALUES(id_tag), vt2 = VALUES(vt2), active = VALUES(active)"
        );
        $this->logs->add("video_tag_custom: " . $this->affectedRows());
    }

    public function download($videoRows)
    {
        $cnt = 0;
        $this->browser->timeOut = 10000;
        $this->logs->add("videoRows: " . count($videoRows));
        foreach ($videoRows as $videoRow) {
            $dir  = ROOT_DIR . "userdata/other/xv/" . $videoRow["hash"][0] . DS . $videoRow["hash"][1] . DS . $videoRow["hash"][2];
            $file = $this->videoFile($videoRow);
            $fileDownload = "/home/sirotkin/video/xxx/" . preg_replace(array ('/\//', '/\:/'), array ("_", "-"), $videoRow["name"]) . ".mp4";
            if (file_exists($file)) {
#                $this->logs->add("already exist: " . $file);
                $this->query("UPDATE video SET size = " . filesize($file) . " WHERE id = " . $videoRow["id"]);
                if ($videoRow["download"] and (!is_file($fileDownload) or filesize($file) > filesize($fileDownload))) {
                    copy($file, $fileDownload);
                    @chmod($fileDownload, 0666);
                }
                continue;
            }
            if (is_dir($dir)) {
                $scanDir = scandir($dir);
                foreach ($scanDir as $fileExist) {
                    if (0 === mb_strpos($fileExist, $videoRow["id"])) {
                        rename($dir . DS . $fileExist, $file);
                        $this->logs->add("rename: " . $fileExist . " to " . $file);
                        continue 2;
                    }
                }
            }
            $href = $this->site . "/video" . $videoRow["id"] . "/" . $videoRow["name_tag"];
            $att = 0;
            do {
                $html = $this->browser->request($href);
                if (!$html) {
                    $this->logs->add("empty: " . $href);
                    $att ++;
                    sleep($att * 35);
                }
            } while (!$html and $att < 5);
            if (!$html) {
                continue;
            }
            $pos = mb_strpos($html, "html5player.setVideoUrlHigh");
            if (false === $pos) {
                $this->logs->add("not find urlHigh: " . $href);
                continue;
            }
            $pos    = mb_strpos($html, "http://", $pos);
            $posEnd = mb_strpos($html, "');", $pos);
            $url    = mb_substr($html, $pos, $posEnd - $pos);
            if (mb_strlen($url) > 512) {
                $this->logs->add("wrong video url in : " . $href);
                continue;
            }
            $t   = microtime(true);
            $res = $this->browser->request($url);
            if (!$res) {
                $this->logs->add("empty res from: " . $url . " at " . (microtime(true) - $t) . "s.");
                continue;
            }
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($file, $res);
            @chmod($file, 0666);
            if (!file_exists($file)) {
                $this->logs->add("not exist: " . $file . " after load from: " . $url);
                continue;
            }
            $this->query("UPDATE video SET size = " . filesize($file) . " WHERE id = " . $videoRow["id"]);
            if ($videoRow["download"] and (!is_file($fileDownload) or filesize($file) > filesize($fileDownload))) {
                copy($file, $fileDownload);
                @chmod($fileDownload, 0666);
            }
            $cnt ++;
        }
        return $cnt;
    }

    public function videoFile(&$videoRow)
    {
        $videoRow["file"] = $videoRow["hash"][0] . DS . $videoRow["hash"][1] . DS . $videoRow["hash"][2] . DS . $videoRow["id"] . ".dat";
        return ROOT_DIR . "userdata/other/xv/" . $videoRow["file"];
    }

    public function queryCnt($table, $conditions)
    {
        return $this->queryInt("SELECT COUNT(*) FROM `" . $this->escapeString($table) . "` WHERE " . join (" AND ", $conditions));
    }

    public function rename($dir = "userdata/other/xv/")
    {
        $fileRows = scandir(ROOT_DIR . $dir);
        foreach ($fileRows as $file) {
            if (in_array($file, [".", ".."])) {
                continue;
            }
            $matches = [];
            if (!preg_match('/[0-9a-f]{32}/', $file, $matches)) {
                continue;
            }
            $xvRow = $this->selectRow("video", ["hash" => $matches[0]]);
            if (!$xvRow) {
                continue;
            }
            rename(ROOT_DIR . $dir . $file, ROOT_DIR . $dir . $xvRow["name"] . ".mp4");
        }
    }
    /**
     * @throws
     */
    private function parceDiv(&$divObj, $id, $attr = array ())
    {
#var_dump($divObj->outertext);

        $htmlObj = \voku\helper\HtmlDomParser::str_get_html($divObj->innertext);
        $imgObj  = $htmlObj->find("img", 0);
        if (!$imgObj) {
            throw new Exception("not found img in (" . $id . ") : " . $htmlObj->innertext . "\n");
        }
        $aObj  = $htmlObj->find("a", 0);
        if (!$aObj) {
            throw new Exception("not found a[0] in (" . $id . ") : " . $htmlObj->innertext . "\n");
        }
        $a1Obj = $htmlObj->find("a", 1);
        if ($a1Obj) {
            if (in_array($a1Obj->href, array ("/verified/videos", "/verified-men/videos"))) {
                $a1Obj = $htmlObj->find("a", 2);
                if (!$a1Obj) {
                    throw new Exception("not found a[2] in (" . $id . ") : " . $htmlObj->innertext . "\n");
                }
            }
#var_dump($a1Obj->getAllAttributes());
        } else {
            $a1Obj = $divObj->find("a", 0);
            if (!$a1Obj) {
                throw new Exception("not found a[0] in (" . $id . ") : " . $divObj->innertext . "\n");
            }
            if (in_array($a1Obj->href, array ("/verified/videos", "/verified-men/videos"))) {
                $a1Obj = $divObj->find("a", 1);
                if (!$a1Obj) {
                    throw new Exception("not found a[1] in (" . $id . ") : " . $divObj->innertext . "\n");
                }
            }
#var_dump($a1Obj->getAllAttributes());
        }
        $videoRow = array (
            "id"         => $id,
            "name"       => trim($a1Obj->plaintext),
            "name_tag"   => $this->nameTag(trim($a1Obj->href)),
            "hash"       => $this->getImgSrcHash(trim($imgObj->src)),
            "duration"   => "",
            "rate"       => 0,
            "id_profile" => 0,
            "hd"         => 0,
            "created"    => date("YmdHis"),
            "_updated"   => date("YmdHis"),
        );
        $videoRow = array_merge($videoRow, $attr);
        $spanObj = $divObj->find("span.bg", 0);
        if ($spanObj) {
            $strongObj = $spanObj->find("strong", 0);
            if ($strongObj) {
                $videoRow["duration"] = trim($strongObj->plaintext);
            }
            $out = array ();
            if (preg_match('/(\d+)\%/', $spanObj->plaintext, $out)) {
                $videoRow["rate"] = $out[1];
            }
            $aProfileObj = $spanObj->find("a", 0);
            if (!$videoRow["id_profile"] and $aProfileObj) {
                $profileRow = $this->selectRow("profile", ["name_tag" => $aProfileObj->href]);
                if (!$profileRow) {
                    $profileRow = array (
                        "name"     => trim($aProfileObj->plaintext),
                        "name_tag" => $aProfileObj->href,
                    );
                    $profileRow["id"] = $this->insertOrUpdateRow("profile", $profileRow);
                }
                $videoRow["id_profile"] = $profileRow["id"];
            }
        }
        $spanHdObj = $divObj->find("span.video-hd-mark", 0);
        if ($spanHdObj) {
            $videoRow["hd"] = 1;
        }
        return $videoRow;
    }

    private function nextPageTag(&$htmlObj)
    {
        $divObj = $htmlObj->find("div.pagination", 0);
        if (!$divObj) {
            return;
        }
        $aCurObj = $divObj->find("a.active", 0);
        if (!$aCurObj) {
            return;
        }
        $liCurObj = $aCurObj->parentNode();
        if (!$liCurObj) {
            return;
        }
        $liNextObj = $liCurObj->nextSibling();
        if (!$liNextObj) {
            return;
        }
        $aNextObj = $liNextObj->find("a", 0);
        if (!$aNextObj) {
            return;
        }
        return $aNextObj->href;
    }

    private function nextPageProfile(&$htmlObj)
    {
        return $this->nextPageTag($htmlObj);
    }

}