<?php

$content    = "";
$pathInfo   = pathinfo($_SERVER["REDIRECT_URL"]);
$file       = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "js" . DIRECTORY_SEPARATOR . $pathInfo["basename"];

if (is_file($file)) {
    $content = file_get_contents($file);
}

switch ($pathInfo["extension"]) {
    case "js":
        header("Content-Type:application/javascript");
        break;

    default:
        header("Content-Type:application/octet-stream");
        break;
}
echo($content);