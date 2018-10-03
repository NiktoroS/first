<?php
/**
 * @category Library
 * @package  http requests with file_get_contents
 * @author <first@mail.ru>
 * @since  2018-04-10
 */

require_once(INC_DIR . "vendor/wikia/simplehtmldom/simple_html_dom.php");

class Browser {

#    public $useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko";
    public $useragent = "Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36 Edge/12.0";
    public $timeOut   = 300;
    public $referer   = "";
    public $cookies   = "";
    public $proxy;
    public $url;
    public $res;
    public $resHeader;
    public $userPwd;

    public function __construct($proxy = "") {
        $this->proxy = $proxy;
    }

    public function request($url, $httpMethod = "GET", $httpHeader = array (), $content = "") {
        $this->url = $url;
        $aContext = array (
            "http" => array (
                "method" => $httpMethod,
                "header" => $this->makeHeader($httpHeader),
                "timeout"    => $this->timeOut,
            ),
        );
        if ($content) {
            if (is_array($content)) {
                $content = http_build_query($content);
            }
            $aContext["http"]["header"] .= "Content-Length: ". strlen($content) . PHP_EOL;
            $aContext["http"]["content"] = $content;
        }
        if ($this->proxy) {
            $aContext["http"]["proxy"] = $this->proxy;
            $aContext["http"]["request_fulluri"] = true;
        }
#var_dump($aContext);
        $http_response_header = array ();
        if (1 == 2 or ($this->proxy and preg_match('/^socks5/', $this->proxy))) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, explode(PHP_EOL, $aContext["http"]["header"]));
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            if ($this->proxy) {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
            }
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeOut);
            if ("POST" == $httpMethod and !empty($aContext["http"]["content"])) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $aContext["http"]["content"]);
            }
            $resRows = explode("\r\n\r\n", curl_exec($ch));
            $http_response_header = explode("\r\n", array_shift($resRows));
            $this->res = implode("\n", $resRows);
            curl_close($ch);
        } else {
            $cxContext = stream_context_create($aContext);
            $this->res = @file_get_contents($url, false, $cxContext);
        }
        $this->resHeader = $http_response_header;
        $this->getCookies();
        $this->referer = $url;
        return $this->res;
    }

    public function requestHtml($url, $httpMethod = "GET", $httpHeader = array (), $content = "") {
        $this->request($url, $httpMethod, $httpHeader, $content);
        if ($this->res) {
            return str_get_html($this->res);
        }
    }

    public function getForms() {
        $htmlObj = str_get_html($this->res);
        $forms = array ();
        foreach ($htmlObj->find("form") as $formObj) {
            $action = $formObj->action ? $formObj->action : "";
            $method = $formObj->method ? $formObj->method : "GET";
            foreach ($formObj->find("input") as $inputObj) {
                $forms[$action][$method][$inputObj->name] = $inputObj->value ? $inputObj->value : "";
            }
            foreach ($formObj->find("textarea") as $textareaObj) {
                $forms[$action][$method][$textareaObj->name] = $textareaObj->value ? $textareaObj->value : "";
            }
        }
        return $forms;
    }

    private function getCookies() {
        if (!$this->resHeader) {
            return $this->cookies;
        }
        $cookies  = array();
        if ($this->cookies) {
            $cookiesArr = explode("; ", $this->cookies);
            foreach ($cookiesArr as $cookiesRow) {
                if ($cookiesRow) {
                    list($var, $val) = explode("=", $cookiesRow);
                    $cookies[$var] = $val;
                }
            }
        }
        $filtr = '/Set-Cookie:\s(\S+[^=])=(\S*[^;\s])/';
        foreach ($this->resHeader as $responseRow) {
            $out = array ();
            if (preg_match($filtr, $responseRow, $out)) {
                $cookies[$out[1]] = $out[2];
            }
        }
        $this->cookies = "";
        foreach ($cookies as $var => $val) {
            $this->cookies .= $var . "=" . $val . "; ";
        }
        return $this->cookies;
    }

    private function makeHeader($httpHeader = array ()) {
        $header = "";
        foreach ($httpHeader as $var => $val) {
            $header .= $var . ": " . $val . PHP_EOL;
        }
        if ($this->cookies and empty($httpHeader["Cookie"])) {
            $header .= "Cookie: " . $this->cookies . PHP_EOL;
        }
        if (empty($httpHeader["Content-type"])) {
            $header .= "Content-Type: application/x-www-form-urlencoded; charset=UTF-8" . PHP_EOL;
        }
        if (empty($httpHeader["User-Agent"])) {
            $header .= "User-Agent: " . $this->useragent . PHP_EOL;
        }
        if (empty($httpHeader["Host"])) {
            $urlArr = parse_url($this->url);
            $header .= "Host: " . $urlArr["host"] . PHP_EOL;
        }
        if ($this->referer and empty($httpHeader["Referer"])) {
            $header .= "Referer: " . $this->referer . PHP_EOL;
        }
        return $header;
    }

}