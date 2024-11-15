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

    public function deleteMessage($update)
    {
        var_dump([
            "chat_id"       => $update->message->chat->id,
            "message_id"    => $update->message->message_id
        ]);
        return $this->api->deleteMessage([
            "chat_id"       => $update->message->chat->id,
            "message_id"    => $update->message->message_id
        ]);
    }

    public function downloadFile($filePath)
    {
        $cxContext = stream_context_create([
            "ssl" => [
                "verify_peer"       => false,
                "verify_peer_name"  => false
            ],
        ]);
        return file_get_contents(
            "https://api.telegram.org/file/bot{$this->botToken}/{$filePath}",
            false,
            $cxContext
        );
    }

    public function getChat($chatId = 205579980)
    {
        return $this->api->getChat(['chat_id' => $chatId]);
    }

    public function getFile($fileId = "")
    {
        return $this->api->getFile(["file_id" => $fileId]);
    }

    public function getMe()
    {
        return $this->api->getMe();
    }

    public function getUpdates($offset = 0, $shouldDispatchEvents = false)
    {
        return $this->api->getUpdates(["offset" => $offset], $shouldDispatchEvents);
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