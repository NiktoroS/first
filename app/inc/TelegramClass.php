<?php
namespace app\inc;

require_once(INC_DIR . "BrowserClass.php");
require_once(INC_DIR . "Logs.php");
require_once(INC_DIR . "ProxyClass.php");

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
    private $proxyObj;

    public function __construct($botToken = "329014600:AAGB3v56moIsLum3gsfiNsE6-9u4WKGqOrg" /* @niktorisBot */)
    {
        $this->botToken = $botToken;
        $this->browser  = new BrowserClass();
        $this->browser->timeOut = 5;
        $this->logs     = new Logs("Telegram");
        $this->proxyObj = new ProxyClass();
    }

    /**
     *
     * @param \stdClass $update
     * @return string
     */
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

    /**
     *
     * @param string $filePath
     * @return string
     */
    public function downloadFile($filePath = "")
    {
        return $this->request($filePath, [], "GET", "/file/bot");
    }

    /**
     *
     * @param number $chatId
     * @return string
     */
    public function getChat($chatId = 205579980)
    {
        return $this->request("getChat", ["chat_id" => $chatId]);
    }

    /**
     *
     * @param string $fileId
     * @return string
     */
    public function getFile($fileId = "")
    {
        return $this->request("getFile", ["file_id" => $fileId]);
    }

    /**
     *
     * @return string
     */
    public function getMe()
    {
        return $this->request("getMe", [], "GET");
    }

    /**
     *
     * @param number $offset
     * @return array
     */
    public function getUpdates($offset = 0)
    {
        return $this->request("getUpdates", ["offset" => $offset]);
    }

    /**
     *
     * @param string $document
     * @param number $chatId
     * @param string $caption
     * @return string
     */
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

    /**
     *
     * @param string $text
     * @param string $subject
     * @param number $chatId
     * @return string
     */
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

    /**
     *
     * @param string $name
     * @param array $data
     * @param string $httpMethod
     * @param string $dir
     * @param string $httpHeader
     * @param boolean $useCurl
     * @return string
     */
    private function request($name, $data = [], $httpMethod = "POST", $dir = "/bot", $httpHeader = "application/x-www-form-urlencoded", $useCurl = false)
    {
        $this->browser->res = "";
        while (!$this->browser->res) {
            $proxyRow = $this->proxyObj->getProxy();
            $this->browser->proxy = base64_decode($proxyRow["name"]);
            $this->browser->proxyAuth = $proxyRow["auth"] ?? null;
            $microtime = microtime(true);
            echo(date("[Y-m-d H:i:s] ") . "{$dir}/{$name} [{$httpMethod}] {$this->browser->proxy} ");
            $this->browser->request("https://api.telegram.org{$dir}{$this->botToken}/{$name}", $httpMethod, [$httpHeader], $data, $useCurl);
            $time = microtime(true) - $microtime;
            if (!$this->browser->res) {
                $this->proxyObj->saveFail($proxyRow);
            } else {
                if ("getUpdates" === $name) {
                    echo("{$this->browser->res} ");
                }
                if (json_decode($this->browser->res)) {
                    $this->proxyObj->saveSuccess($proxyRow, microtime(true) - $microtime);
                }
            }
            echo(round($time, 2) . "\n");
        }
        return json_decode($this->browser->res)->result ?? $this->browser->res;
    }
}