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

class TelegramClass
{

    private $api;
    private $botToken = "329014600:AAGB3v56moIsLum3gsfiNsE6-9u4WKGqOrg"; // @niktorisBot:

    public function __construct()
    {
        $this->api = new Api($this->botToken);
    }

    public function sendDocument($document, $caption = "", $chatId = 205579980)
    {
        return $this->api->sendDocument([
            'chat_id' => $chatId,
            'caption' => $caption,
            'document' => InputFile::create($document)
        ]);
    }

    public function sendMessage($text, $subject = "Message", $chatId = 205579980)
    {
        return $this->api->sendMessage([
            "chat_id" => $chatId,
            "parse_mode" => "HTML",
            "text" => "<b>" . strip_tags(strval($subject)) . "</b><pre>\n" . strip_tags(strval($text)) . "</pre>"
        ]);
    }


}