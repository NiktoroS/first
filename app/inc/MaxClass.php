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

class MaxClass
{

    private $browser;
    private $token;
    private $logs;

    /**
     *
     * @param string $botToken
     */
    public function __construct($token = "")
    {
        $this->botToken = $token;
        $this->browser  = new BrowserClass();
        $this->logs     = new Logs("Telegram");
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
//        return $this->request("getFile", ["file_id" => $fileId]);
        return $this->request("getFile?file_id={$fileId}", [], "GET");
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
        return $this->request("getUpdates" . ($offset ? "?offset={$offset}" : ""), [], "GET");
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
            ["Content-Type" => "multipart/form-data"],
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
    private function request($name, $data = [], $httpMethod = "POST", $dir = "/bot", $httpHeader = ["Content-Type" => "application/x-www-form-urlencoded"], $useCurl = false)
    {
        $this->browser->res = "";
        if (getenv("PROXY") && getenv("EXTERNAL_IP") != gethostbyname(gethostname())) {
            $this->browser->proxy = getenv("PROXY");
            if (getenv("PROXY_AUTH")) {
                $this->browser->proxyAuth = getenv("PROXY_AUTH");
            }
        }
        try {
            $this->browser->request("https://web.max.ru{$dir}{$this->token}/{$name}", $httpMethod, $httpHeader, $data, $useCurl);
            echo("{$this->browser->res}");
        } catch (\Exception $exception) {
            echo($exception->getMessage() . " [" . $exception->getCode() . "]\n");
        }
        return json_decode($this->browser->res);
    }
}