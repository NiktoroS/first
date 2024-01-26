<?php

/**
 * @category
 * @package  xv
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    02.03.2021
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "Logs.class.php");

error_reporting(E_ALL);

$logs = new Logs();


try {
    $dir = ROOT_DIR . "content" . DS . "ITC";
    foreach (scandir($dir) as $file) {
        if (!preg_match('/mp4$/', $file)) {
            continue;
        }
        $fileOutput = "2.{$file}";
        if (is_file($fileOutput)) {
            continue;
        }
        var_dump($dir . DS . $file);
        $ffmpeg = FFMpeg\FFMpeg::create([
            'ffmpeg.binaries'  => $dir . DS . 'ffmpeg.exe',
            'ffprobe.binaries' =>  $dir . DS . 'ffprobe.exe',
            'timeout' => 0
        ]);
        $video = $ffmpeg->open($dir . DS . $file);
/*
        $video
        ->filters()
        ->resize(new \FFMpeg\Coordinate\Dimension(1920, 1080), \FFMpeg\Filters\Video\ResizeFilter::RESIZEMODE_INSET, true)
        ->synchronize();
        $video
        ->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(10))
        ->save($file . '.jpg');
        $video
        ->save(new \FFMpeg\Format\Video\X264(), $fileOutput);
*/
        $video
        ->filters()
        ->resize(new FFMpeg\Coordinate\Dimension(1920, 1080), FFMpeg\Filters\Video\ResizeFilter::RESIZEMODE_INSET, true);
        $video->save(new FFMpeg\Format\Video\X264(), $fileOutput);
    }
} catch (Exception $e) {
    var_dump($e);
    $logs->add($e);
}
