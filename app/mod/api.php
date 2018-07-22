<?php
/**
 * rbc.ru
 * @author <first@mail.ru>
 * @since  2018-07-23
 */
require_once MOD_DIR . "main.php";

class api extends main
{

    public function imagick($params = array ())
    {
        $dir = ROOT_DIR . "userdata/first/api" . DS . "imagick";
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (isset($_REQUEST["file_name"]) and isset($_REQUEST["file_data"])) {
            $resolution = 150;
            $imagick = new Imagick();
            $imagick->setResolution($resolution, $resolution);
            $imagick->readImageBlob(base64_decode($_REQUEST["file_data"]));
            // $imagick->setCompressionQuality(50);
            $name = preg_replace('/\.pdf/', ".jpg", $_REQUEST["file_name"]);
            $file = time() . "_" . $name;
            $imagick->writeImages($dir . DS . $file, true);
            $data = array(
                "files" => array(
                    array(
                        "file" => $file,
                        "name" => $name
                    )
                )
            );
            header("Content-type: application/json");
            echo (json_encode($data));
        }
        if (isset($params[0])) {
            header("Content-Type: image/png");
            echo file_get_contents($dir . DS . rawurldecode($params[0]));
        }
        exit();
    }

    public function test()
    {
        if ($_FILES) {
            foreach ($_FILES as $key => $file) {
                if ($file["error"]) {
                    continue;
                }
                $postdata = http_build_query(array(
                    'file_name' => $file["name"],
                    'file_data' => base64_encode(file_get_contents($file["tmp_name"]))
                ));

                $opts = array(
                    'http' => array(
                        'method' => 'POST',
                        'header' => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $postdata
                    )
                );

                $context = stream_context_create($opts);
                $json = file_get_contents("http://first.hldns.ru/api/imagick", false, $context);

                $this->assign(json_decode($json, true));
            }
        }
    }
}