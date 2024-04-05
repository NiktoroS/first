<?php
namespace app\inc;

use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;

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

    public function sendDocument($document, $caption = "", $chatId = 205579980)
    {
        $telegram = new Api($this->botToken);
        return $telegram->sendDocument([
            'chat_id' => $chatId,
            'document' => InputFile::create($document),
            'caption' => $caption
        ]);
    }

}