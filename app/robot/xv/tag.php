<?php
/**
 * @category tag
 * @package  xv
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    26.01.2017
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__, 2) . "/cnf/main.php");
require_once(INC_DIR . "Xv.class.php");
require_once(INC_DIR . "Lock.class.php");

error_reporting(E_ALL);

$copies = 1;

$lock = new Lock();
if (false === ($copy = $lock->setLock($copies))) {
    exit;
}

$logs = new Logs();
$logs->setCopy($copy);

$sql = "
INSERT INTO `tag` (`id`, `cnt_v`)
SELECT `id_tag`, COUNT(*)
  FROM `video_tag`
 WHERE `id_tag` > 0 GROUP BY 1
    ON DUPLICATE KEY UPDATE `cnt_v` = VALUES(`cnt_v`)";
$sql2 = "
INSERT INTO `tag` (`id`, `cnt_v2`)
SELECT `id_tag`, COUNT(*)
  FROM `video_tag2`
 WHERE `id_tag` > 0 GROUP BY 1
    ON DUPLICATE KEY UPDATE `cnt_v2` = VALUES(`cnt_v2`)";

$sql3 = "
UPDATE `video_tag` AS `vt`
  JOIN `video` AS `v` ON  vt.id_video = v.id
   SET vt.active = 0
 WHERE v.err > 0";

$sql4 = "DELETE FROM `video_tag` WHERE `active` = 0";

$sql5 = "
SELECT `name_tag`, MIN(`id`) AS `id_min`, MAX(`id`) AS `id_max`
  FROM `tag`
 WHERE `active` = 1
 GROUP BY 1
HAVING COUNT(*) > 1";
try {

    $xvObj = new xvClass();

    $logs->add("start");
/**/
    $tagDubleRows = $xvObj->queryRows($sql5);
    $logs->add("double tag : " . count($tagDubleRows));
    foreach ($tagDubleRows as $tagDubleRow) {
        $logs->add("process : " . var_export($tagDubleRow, true));

        $xvObj->begin();
        $xvObj->query("
INSERT INTO `video_tag2` (`id_video`, `id_tag`)
SELECT `id_video`, {$tagDubleRow["id_min"]}
  FROM `video_tag2`
 WHERE `id_tag` = {$tagDubleRow["id_max"]}
    ON DUPLICATE KEY UPDATE `active` = VALUES(`active`)");
        $logs->add("video_tag2 affected : " . $xvObj->affectedRows());

        $xvObj->query("UPDATE `video_tag2` SET active = `0` WHERE `id_tag` = {$tagDubleRow["id_max"]}");
        $logs->add("video_tag2 affected : " . $xvObj->affectedRows());

        $xvObj->query("
INSERT INTO `video_tag` (`id_video`, `id_tag`, `vt2`)
SELECT `id_video`, {$tagDubleRow["id_min"]}, `vt2`
  FROM `video_tag`
 WHERE `id_tag = {$tagDubleRow["id_max"]}
    ON DUPLICATE KEY UPDATE `active` = VALUES(`active`)");
        $logs->add("video_tag affected : " . $xvObj->affectedRows());

        $xvObj->query("UPDATE `video_tag` SET `active` = 0 WHERE `id_tag` = {$tagDubleRow["id_max"]}");
        $logs->add("video_tag affected : " . $xvObj->affectedRows());

        $xvObj->query("UPDATE `tag` SET `active` = 0 WHERE `id` = {$tagDubleRow["id_max"]}");
        $xvObj->commit();
    }

    $xvObj->tags();

    $tagRows = $xvObj->selectRows("tag", ["active" => 1, "`id` > 0", "(`cnt` > 0 OR `created` > NOW() - INTERVAL 2 DAY)", "`id` % {$copies} = {$copy}"], true, "`_updated` ASC");
    $logs->add("tagRows: " . count($tagRows));
    $cnt = 0;
    $skipTags = ["/tags/video"];
    foreach ($tagRows as $tagRow) {
        if (in_array($tagRow["name_tag"], $skipTags)) {
            continue;
        }
        $cnt += $xvObj->parceTag($tagRow);
    }

    $xvObj->query($sql);
    $xvObj->query($sql2);
#    $xvObj->query($sql3);
    $xvObj->query($sql4);

    $logs->add("finish : {$cnt}");
} catch (Exception $e) {
    $logs->add($e);
}