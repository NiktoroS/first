<?php
use app\inc\Lock;
use app\inc\Logs;
use app\inc\XvClass;

#exit;
/**
 * @category vt
 * @package  xv
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    28.01.2017
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__, 2) . "/cnf/main.php");
require_once(INC_DIR . "Lock.php");
require_once(INC_DIR . "XvClass.php");

error_reporting(E_ALL);

$copies = 3;
$limit  = 10000;

sleep(rand(0, 59));

$lock = new Lock();
if (false === ($copy = $lock->setLock($copies))) {
    exit;
}

$logs = new Logs();
$logs->setCopy($copy);

try {
    $logs->add("start");

    $xvObj = new XvClass();

    $videoUpdRows = [];
    $vtUpdRows    = [];
    $vmUpdRows    = [];

    $resultObj = $xvObj->queryForSelect("SELECT * FROM `video` WHERE 1 = `active` AND `err` < 5 AND `id` % {$copies} = {$copy} ORDER BY `checked` ASC LIMIT {$limit}");
    $videoKey = 0;
    while ($videoRow = $resultObj->fetch_assoc()) {
        $href = "/video" . $videoRow["id"] . "/" . $videoRow["name_tag"];
        $htmlObj = $xvObj->requestHtml($href);
        if (!$htmlObj) {
            continue;
        }
        $divViewsObj = $htmlObj->find("div.[id='v-actions-container']", 0);
        if (!$divViewsObj) {
            $h1DangerObj = $htmlObj->find("h1.text-danger", 0);
            if ($h1DangerObj) {
                if ("Sorry, this video has been deleted" == $h1DangerObj->plaintext) {
                    $xvObj->query("UPDATE video SET err = 10 WHERE id = " . $videoRow["id"]);
                    continue;
                }
                if ("We received a request to have this video deleted. It has been automatically disabled until we hear from the uploader. It will then be deleted or reinstated." == $h1DangerObj->plaintext) {
                    $xvObj->query("UPDATE video SET err = 20 WHERE id = " . $videoRow["id"]);
                    continue;
                }
                if ("This video has been automatically disabled because it was reported by our users. We will review the reports and delete it permanently or rehabilitate the video." == $h1DangerObj->plaintext) {
                    $xvObj->query("UPDATE video SET err = 30 WHERE id = " . $videoRow["id"]);
                    continue;
                }
                if ("Sorry but the page you requested was not found" == $h1DangerObj->plaintext) {
                    $xvObj->query("UPDATE video SET err = 40 WHERE id = " . $videoRow["id"]);
                    continue;
                }
                $logs->add($h1DangerObj->plaintext . " : " . $href);
                continue;
            }
            if ($xvObj->browser->resHeader and "HTTP/1.1 404 Not Found" == $xvObj->browser->resHeader[0]) {
                $xvObj->query("UPDATE video SET err = err + 1 WHERE id = " . $videoRow["id"]);
                continue;
            }
            $logs->add("not div views in : " . $xvObj->site . $href . " " . $xvObj->browser->resHeader[0]);
            continue;
        }
        $strongViewsObj = $divViewsObj->find("div.[id='v-views']']", 0);
        $spanTotalObj   = $divViewsObj->find("span.rating-total-txt", 0);
        $videoUpdRow = [
            "id"    => $videoRow["id"],
            "name"  => $videoRow["name"],
            "views" => preg_replace('/[\,\s]/', "", trim($strongViewsObj->plaintext)),
            "total" => $spanTotalObj ? intval(preg_replace('[\,\s]', "", trim($spanTotalObj->plaintext))) : 0,
            "hd"    => $videoRow["hd"],
            "id_profile" => $videoRow["id_profile"],
            "err"        => 0,
            "checked"    => date("Y-m-d H:i:s"),
        ];
        $divMain   = $htmlObj->find("div.main", 0);

        $spanHdObj = $htmlObj->find("span.video-hd-mark", 0);
        if ($spanHdObj) {
            $videoUpdRow["hd"] = 1;
        }
        foreach ($htmlObj->find("p.video-metadata") as $pVmObj) {
            if (!$videoRow["id_profile"]) {
                $spanUploaderObj = $pVmObj->find("span.uploader", 0);
                if ($spanUploaderObj) {
                    $aObj = $spanUploaderObj->find("a.hg", 0);
                    $profileRow = $xvObj->selectRow("profile", ["name_tag" => trim($aObj->href)]);
                    if (!$profileRow) {
                        $profileRow = [
                            "name_tag" => trim($aObj->href),
                            "name"     => trim($aObj->plaintext),
                        ];
                        $profileRow["id"] = $xvObj->insertRow("profile", $profileRow);
                    }
                    $videoUpdRow["id_profile"] = $profileRow["id"];
                }
            }
            if (false !== mb_strpos($pVmObj->plaintext, "Models in this video:")) {
                foreach ($pVmObj->find("a.hg") as $aObj) {
                    $modelRow = $xvObj->selectRow("profile", ["name_tag" => trim($aObj->href)]);
                    if (!$modelRow) {
                        $modelRow = [
                            "name_tag" => trim($aObj->href),
                            "name"     => trim($aObj->plaintext),
                        ];
                        $modelRow["id"] = $xvObj->insertRow("profile", $modelRow);
                    }
                    $vmUpdRows[] = array (
                        "id_video" => $videoRow["id"],
                        "id_model" => $modelRow["id"],
                        "active"   => 1
                    );
                }
            }
            $spanTagsObj = $pVmObj->find("span.video-tags", 0);
            if ($spanTagsObj) {
                foreach ($spanTagsObj->find("a") as $aObj) {
                    if (!in_array($aObj->href, ["/tags/", "/verified/videos", "/verified-men/videos"])) {
                        $tagRow = $xvObj->selectRow("tag", ["name_tag" => trim($aObj->href)]);
                        if (!$tagRow) {
                            $tagRow = [
                                "name_tag" => trim($aObj->href),
                                "name"     => trim($aObj->plaintext),
                            ];
                            $tagRow["id"] = $xvObj->insertRow("tag", $tagRow);
                        }
                        $vtUpdRows[] = [
                            "id_video" => $videoRow["id"],
                            "id_tag"   => $tagRow["id"],
                            "vt2"      => 1,
                            "active"   => 1
                        ];
                    }
                }
            }
        }
        $videoUpdRows[] = $videoUpdRow;
        $videoKey ++;
        if (0 == $videoKey % ($limit / 5)) {
            $logs->add($videoKey);
            if ($videoUpdRows) {
                $xvObj->insertOrUpdateRows("video", $videoUpdRows);
                $logs->add("video: " . $xvObj->affectedRows());
                $videoUpdRows = array ();
            }
        }
    }

    if ($videoUpdRows) {
        $xvObj->insertOrUpdateRows("video", $videoUpdRows);
        $logs->add("video: " . $xvObj->affectedRows());
    }

    $lock->delLock();

    if ($vtUpdRows) {
        $xvObj->insertOrUpdateRows("video_tag", $vtUpdRows);
        $logs->add("video_tag: " . $xvObj->affectedRows());
        $xvObj->insertOrUpdateRows("video_tag2", $vtUpdRows);
        $logs->add("video_tag2: " . $xvObj->affectedRows());
    }
    if ($vmUpdRows) {
        $xvObj->insertOrUpdateRows("video_model", $vmUpdRows);
        $logs->add("video_model: " . $xvObj->affectedRows());
    }
    $logs->add("finish : " . $xvObj->queryCnt("video", ["`active` = 1", "`err` = 0"]));

} catch (Exception $e) {
    $logs->add($e);
}