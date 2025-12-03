<?php
namespace app\inc;

require_once(INC_DIR . "BrowserClass.php");
require_once(INC_DIR . "Logs.php");

/**
 * Telegram
 *
 * @author Andrey A. Sirotkin <first@mail.ru>
 * @since  20.12.2016
 */

class TelegramClass
{

    private $browser;
    private $botToken;
    private $logs;

    public function __construct($botToken = "329014600:AAGB3v56moIsLum3gsfiNsE6-9u4WKGqOrg" /* @niktorisBot */)
    {
        $this->botToken = $botToken;
        $this->browser  = new BrowserClass(getenv("PROXY") ?? "", getenv("PROXY_AUTH") ?? "");
        $this->logs = new Logs("Telegram");
    }

    public function deleteMessage($update)
    {
        return $this->request(
            "deleteMessage",
            [
                "chat_id"       => $update->message->chat->id,
                "message_id"    => $update->message->message_id
            ]
        );
    }

    public function downloadFile($filePath)
    {
        return $this->request($filePath, [], "GET", "/file/bot");
    }

    public function getChat($chatId = 205579980)
    {
        return $this->request("getChat", ["chat_id" => $chatId]);
    }

    public function getFile($fileId = "")
    {
        return $this->request("getFile", ["file_id" => $fileId]);
    }

    public function getMe()
    {
        return $this->request("getMe", "GET");
    }

    /**
     *
     * @param number $offset
     * @param boolean $shouldDispatchEvents
     * @return array
     */
    public function getUpdates($offset = 0, $shouldDispatchEvents = false)
    {
        $result = $this->request("getUpdates", ["offset" => $offset]);
        if ($shouldDispatchEvents) {
            var_dump($result);
        }
        return $result;
    }

    public function sendDocument($document, $chatId = 205579980, $caption = "")
    {
        return $this->request(
            "sendDocument",
            [
                "caption"   => $caption,
                "chat_id"   => $chatId,
                "document"  => new \CURLFile($document, null, basename($document))
            ],
            "POST",
            "/bot",
            "multipart/form-data",
            true
        );
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

    private function request($name, $data = [], $httpMethod = "POST", $dir = "/bot", $httpHeader = "application/x-www-form-urlencoded", $useCurl = false)
    {
        try {
//            echo("{$name}: " . var_export($data, true));
            $this->browser->request("https://api.telegram.org{$dir}{$this->botToken}/{$name}", $httpMethod, [$httpHeader], $data, $useCurl);
            if (!$this->browser->res) {
                throw new \Exception("Empty response from: {$this->browser->url}");
            }
            return json_decode($this->browser->res)->result ?? $this->browser->res;
        } catch (\Exception $exception) {
            $this->logs->add($exception->getMessage());
            throw $exception;
        }
    }
}