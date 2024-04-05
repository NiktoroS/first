<?php
namespace app\inc;

/**
 * Telegram
 *
 * @author Andrey A. Sirotkin <first@mail.ru>
 * @since  20.12.2016
 */
require_once(INC_DIR . "BrowserClass.php");

class TelegramClass
{

    private $botToken = "329014600:AAGB3v56moIsLum3gsfiNsE6-9u4WKGqOrg"; // @niktorisBot:
    private $webSite  = "https://api.telegram.org/bot";

    public function request($method = "getMe", $params)
    {
        $browser = new BrowserClass();
        $httpHeader = [
            "User-Agent" => "MyAgent/1.0"
        ];
        $res = $browser->request("{$this->webSite}{$this->botToken}/{$method}", "POST", $httpHeader, $params);
        return json_decode($res, true);
    }

    public function sendMessage($text, $subject = "Message", $chatId = 205579980)
    {
        return $this->request(
            "sendMessage",
            [
                "chat_id" => $chatId,
                "parse_mode" => "HTML",
                "text" => "<b>" . strip_tags(strval($subject)) . "</b><pre>\n" . strip_tags(strval($text)) . "</pre>"
            ]
        );
    }

    public function sendDocument($file, $name = "test.txt", $chatId = 205579980)
    {
        if (is_file($file)) {
            $document = curl_file_create($file, mime_content_type($file), $name);
        } else {
            $document = $file;
        }
        $finfo = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file);
        $cFile = new \CURLFile($file, $finfo);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->webSite}{$this->botToken}/sendDocument?chat_id={$chatId}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            "document" => $cFile
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
/*
        return $this->request(
            "sendDocument",
            [
                "chat_id" => $chatId,
                'caption' => 'Проверка работы',
                "document" => $document
            ]
        );
*/
    }

}