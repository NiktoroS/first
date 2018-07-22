<?php
/**
 * @category различные утилиты
 * @author <first@mail.ru>
 * @since  2018-03-30
 */

class Tools
{

    /**
     * вывод в стиле логирования
     */
    static public function outputLog($var)
    {
        echo (date("[Y-m-d H:i:s] ") . (string)$var . "\n");
    }

    static public function outputError($string)
    {
        require_once INC_DIR . "Telegram.class.php";
        $telegram = new Telegram();
        $telegram->sendMessage($string, "error");
        fwrite(STDERR, strval($string));
    }

    static public function preparePhone($phone)
    {
        $phoneArr = array();
        if (preg_match_all('/\d{1}/', $phone, $phoneArr)) {
            $phone = implode("", $phoneArr[0]);
        }
        if (mb_strlen($phone) == 11) {
            if (in_array($phone[0], array("7", "8"))) {
                $phone = mb_substr($phone, 1);
            }
        }
        return $phone;
    }

    static public function checkPhone($phone, $mobile = false)
    {
        $res = true;
        if (empty($phone)) {
            $res = "не указан телефон";
        } else {
            $phone = self::preparePhone($phone);
            if (empty($phone) or mb_strlen($phone) != 10) {
                $res = "не верно указан номер телефона 10-11 цифр (прим: 495-123-45-67, +7-916-12-000-34)";
            }
            if ($mobile and in_array(mb_substr($phone, 0, 3), array("495", "496", "499"))) {
                $res = "не верно указан номер мобильного телефона";
            }
        }
        return $res;
    }

    static public function location($location, $code = 301)
    {
        if ($_SERVER["REQUEST_URI"] == $location) {
#            exit("invalid redirect url");
            return;
        }
        $textArr = array(
            301 => "Moved Permanently",
            302 => "Moved Temporarily ",
            307 => "Temporary Redirect",
        );

        header("HTTP/1.1 " . $code . " " . $textArr[$code]);
        header("Location: " . $location);
        echo "<html>\n";
        echo "<head>\n";
        echo "<title>Status " . $code . " - " . $textArr[$code] . "</title>\n";
        echo "</head>\n";
        echo "<body>\n";
        echo "<script language=\"JavaScript\">\n";
        echo "<!--\n";
        echo "window.location.href = '" . $location . "';\n";
        echo "//-->\n";
        echo "</script>\n";
        echo $textArr[$code] . ": <a href=\"" . $location . "\">" . $location . "</a>\n";
        echo "</body>\n";
        echo "</html>\n";
        exit;
    }

    static public function parseQueryString($str)
    {
        $op = array();
        $pairs = explode("&", $str);
        foreach ($pairs as $pair) {
            list($k, $v) = array_map("urldecode", explode("=", $pair));
            $op[$k] = $v;
        }
        return $op;
    }

    static public function clearHtml($str)
    {
        return preg_replace(array('/\s+/', '/\s?[\r\n]+\s?/', '/\>\s+\</'), array(" ", "", "><"), $str);
    }

    static public function copyResampledFile($srcFile, $dstFile, $maxWidth, $maxHeight, $quality)
    {
        list($srcWidth, $srcHeight, $type) = getimagesize($srcFile);
        $k = max($srcWidth / $maxWidth, $srcHeight / $maxHeight);

        if ($k <= 1) {
            // уменьшать - не требуется
            $k = 1;
        }

        $types = array (
            1 => "GIF",
            2 => "JPEG",
            3 => "PNG",
        );
        if (empty($types[$type])) {
            // неизвестный тип файла
            return @copy($srcFile, $dstFile);
        }

        $dstWidth  = round($srcWidth / $k);
        $dstHeight = round($srcHeight / $k);

        $dst = imagecreatetruecolor($dstWidth, $dstHeight);
        $src = call_user_func("imageCreateFrom" . $types[$type], $srcFile);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
        return call_user_func("imageJPEG", $dst, $dstFile, $quality);
    }

    static public function escapeString($string)
    {
        require_once INC_DIR . "Storage/MySqlStorage.php";
        $mySqlStorage = new MySqlStorage();
        return $mySqlStorage->escapeString($string);
    }

    static public function transDate($string)
    {
        $out = array();
        if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})/', $string, $out)) {
            $string = $out[3] . "-" . $out[2] . "-" . $out[1];
        }
        return $string;
    }

    /**
     * Склоняем словоформу
     * @author runcore
     */
    static private function morph($n, $f1, $f2, $f5)
    {
        $n = abs(intval($n)) % 100;
        if ($n > 10 && $n < 20) {
            return $f5;
        }
        $n = $n % 10;
        if ($n > 1 && $n < 5) {
            return $f2;
        }
        if ($n == 1) {
            return $f1;
        }
        return $f5;
    }

    static public function shuffle_assoc(&$array)
    {
        $keys = array_keys($array);
        shuffle($keys);
        foreach($keys as $key) {
            $new[$key] = $array[$key];
        }
        $array = $new;
        return true;
    }

    static public function textNoice($text)
    {
        $symbols = array(
            "а" => "a",
            "е" => "e",
            "о" => "o",
            "р" => "p",
            "с" => "c",
            "у" => "y",
            "х" => "x",
        );
        $text = iconv("UTF-8", "Windows-1251", $text);
        for($i = 0; $i < strlen($text); $i++) {
            $char = iconv("Windows-1251", "UTF-8", $text[$i]);
            if (in_array($char, array_keys($symbols)) and rand(0, 2) > 1) {
                $text[$i] = iconv("UTF-8", "Windows-1251", $symbols[$char]);
            }
        }
        return iconv("Windows-1251", "UTF-8", $text);
    }
}
