<?php
namespace app\inc;

/**
 * @category Library
 * @package  http requests with file_get_contents
 * @author <first@mail.ru>
 * @since  2018-04-10
 */
//require_once(ROOT_DIR . "/vendor/wikia/simplehtmldom/simple_html_dom.php");

class BrowserClass
{

    public $useragent = "Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36 Edge/12.0";
    public $timeOut   = 300;
    public $referer   = "";
    public $cookies   = "";
    public $proxy;
    public $proxyAuth = "";
    public $url;
    public $res;
    public $resHeader;
    public $userPwd;

    public function __construct($proxy = "", $proxyAuth = "")
    {
        $this->proxy = $proxy;
        $this->proxyAuth = $proxyAuth;
    }

    /**
     *
     * @param string $url
     * @param string $httpMethod
     * @param array $httpHeader
     * @param string|array $content
     * @param boolean $useCurl
     * @param array $curlRows
     * @return string
     */
    public function request($url, $httpMethod = "GET", $httpHeader = [], $content = "", $useCurl = false, $curlRows = [])
    {
        $this->url = $url;
        $aContext = [
            "http" => [
                "ignore_errors" => true,
                "header"    => $this->makeHeader($httpHeader),
                "method"    => $httpMethod,
                "timeout"   => $this->timeOut
            ],
            "ssl" => [
                "verify_peer"       => false,
                "verify_peer_name"  => false
            ]
        ];
        if ($content) {
            $aContext["http"]["content"]    = is_array($content) ? http_build_query($content) : $content;
        }
        if ($this->proxy) {
            $aContext["http"]["proxy"] = "tcp://{$this->proxy}";
            $aContext["http"]["request_fulluri"] = true;
            if ($this->proxyAuth) {
                $aContext["http"]["header"][] = "Proxy-Authorization: Basic " . base64_encode($this->proxyAuth);
            }
        }
        $http_response_header = [];
        if ($useCurl or $this->proxy) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aContext["http"]["header"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeOut);
            curl_setopt($ch, CURLOPT_URL, $url);
            if ($this->proxy) {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                if (preg_match('/^socks4/', $this->proxy)) {
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
                } else if (preg_match('/^socks5/', $this->proxy)) {
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                } else {
                    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
//                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTPS);
                }
                curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
                if ($this->proxyAuth) {
                    curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyAuth);
                }
            }
            if ("POST" == $httpMethod and $content) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
            }
            if ($curlRows) {
                curl_setopt_array($ch, $curlRows);
            }
            $this->res = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new \Exception(curl_error($ch), curl_errno($ch));
            }
            $resRows = explode("\r\n\r\n", $this->res);
//            $http_response_header = explode("\r\n", array_shift($resRows));
            $this->res = implode("\n", $resRows);
            curl_close($ch);
        } else {
            if (!empty($aContext["http"]["content"])) {
                $aContext["http"]["header"][]   = "Content-Length: " . strlen($aContext["http"]["content"]);
            }
            $cxContext = stream_context_create($aContext);
            set_error_handler(function($errno, $errstr, $errfile, $errline) {
                throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
            });
            try {
                $this->res = file_get_contents($url, false, $cxContext);
            } catch (\ErrorException $exception) {
                throw $exception;
            } finally {
                restore_error_handler();
            }
        }
        $this->resHeader = $http_response_header;
        $this->getCookies();
        $this->referer = $url;
        return $this->res;
    }

    public function requestHtml($url, $httpMethod = "GET", $httpHeader = [], $content = "")
    {
        $this->request($url, $httpMethod, $httpHeader, $content);
        if ($this->res) {
            return str_get_html($this->res);
        }
    }

    public function getForms()
    {
        $htmlObj = str_get_html($this->res);
        $forms = [];
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

    private function getCookies()
    {
        if (!$this->resHeader) {
            return $this->cookies;
        }
        $cookies  = [];
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
            $out = [];
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

    private function makeHeader($httpHeader = [])
    {
        $header = [];
        foreach ($httpHeader as $var => $val) {
            $header[] = "{$var}: {$val}";
        }
        if ($this->cookies and empty($httpHeader["Cookie"])) {
            $header[] = "Cookie: {$this->cookies}";
        }
        if (empty($httpHeader["Content-Type"])) {
            $header[] = "Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
        }
//         if (empty($httpHeader["User-Agent"])) {
//             $header[] = "User-Agent: {$this->useragent}";
//         }
//         if (empty($httpHeader["Host"])) {
//             $urlArr = parse_url($this->url);
//             $header[] = "Host: {$urlArr["host"]}";
//         }
//         if ($this->referer and empty($httpHeader["Referer"])) {
//             $header[] = "Referer: {$this->referer}";
//         }
        return $header;
    }

}