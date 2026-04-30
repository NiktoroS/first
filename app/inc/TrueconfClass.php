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

class TrueconfClass
{

    public  $access_token;

    private $browser;
    private $logs;
    private $password = "10Oct30May1975!1";
    private $username = "andrey.a.sirotkin@vlg-tcs-cl01.megafon.ru";
    private $url = "https://vlg-tcs-cl01.megafon.ru/";

    public function __construct()
    {
        $this->browser  = new BrowserClass(); //getenv("PROXY"), getenv("PROXY_AUTH"));
        $this->browser->timeOut = 60;
        $this->logs     = new Logs("Trueconf");
    }

   /**
     *
     *
     * @param \stdClass $update
     * @return string
     */
    public function authorization()
    {
        $response = $this->request(
            "api/v4/oauth2/token",
            json_encode([
                "client_id"     => "trueconf_server_users",
                "grant_type"    => "password",
                "password"      => $this->password,
                "username"      => $this->username
            ])
        );
        if (!$response->access_token) {
            throw new \Exception("not found access_token in response: " . json_encodew($response));
        }
        $this->access_token = $response->access_token;
        return $response;
    }

    public function broadcastPresets()
    {
        return $this->request("api/v3.11/broadcast/presets?access_token={$this->access_token}", [], "GET");
    }

    public function getListOfChats()
    {
        return $this->request(
            "api/v2",
            json_encode([
                "method"     => "getListOfChats",
                "requestId"  => "1"
            ])
        );
    }

    public function groups()
    {
        return $this->request("api/v3.10/groups?access_token={$this->access_token}", [], "GET");
    }

    public function logsMessages()
    {
        return $this->request("api/v3.10/logs/messages?access_token={$this->access_token}", [], "GET");
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
    public function request($name, $data = [], $httpMethod = "POST", $httpHeader = ["Content-Type" => "application/json"], $useCurl = true)
    {
        echo(date("[Y-m-d H:i:s] ") . "{$this->url}{$name} [{$httpMethod} " . (is_array($data) ? json_encode($data) : $data) . "] {$this->browser->proxy}");
        if ($this->access_token) {
            $httpHeader["Authorization"] = "Bearer {$this->access_token}";
        }
        var_dump($httpHeader);
        $this->browser->request("{$this->url}{$name}", $httpMethod, $httpHeader, $data, $useCurl);
        return json_decode($this->browser->res);
    }

    /**
     *
     * @param string $method
     * @return string
     */
    public function requestGet($method = "")
    {
        var_dump($method);
        return $this->request("api/v3.10/{$method}?access_token={$this->access_token}", [], "GET");
    }

    /**
     *
     * @param string $method
     * @param array $data
     * @return string
     */
    public function requestPost($method = "", $data = [])
    {
        var_dump($method);
        return $this->request("api/v3.10/{$method}?access_token={$this->access_token}", json_encode($data));
    }

    public function server()
    {
        $this->request("api/v4/auth/manifest?version=5.5.2.10975&lang=ru&os=web", [], "GET");
        $this->request("api/v4/server", [], "GET");
    }


    public function users()
    {
        return $this->request("api/v3.10/users?access_token={$this->access_token}", [], "GET");
    }

}