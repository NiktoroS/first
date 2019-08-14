<?php
/**
 * Telegram
 *
 * @author Andrey A. Sirotkin <first@mail.ru>
 * @since  20.12.2016
 */
require_once(INC_DIR . "Browser.class.php");

class Telegram
{

    private $botToken = "329014600:AAGB3v56moIsLum3gsfiNsE6-9u4WKGqOrg"; // @niktorisBot:
    private $webSite  = "https://api.telegram.org/bot";

    public function request($method = "getMe", $params)
    {
        $browser = new Browser("socks5://127.0.0.1:9050");
        $httpHeader = array (
            "User-Agent" => "MyAgent/1.0"
        );
        $res = $browser->request($this->webSite . $this->botToken . "/" . $method, "POST", $httpHeader, $params);
        return json_decode($res, true);
    }

    public function sendMessage($text, $subject = "Message", $chatId = 205579980)
    {
        return $this->request(
            "sendMessage",
            array (
                "chat_id" => $chatId,
                "parse_mode" => "HTML",
                "text" => "<b>" . strip_tags(strval($subject)) . "</b><pre>\n" . strip_tags(strval($text)) . "</pre>"
            )
        );
    }

}