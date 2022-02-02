<?php

/**
 * @category
 * @package  xv
 * @author   Andrey A. Sirotkin <myposta@mail.ru>
 * @since    02.03.2021
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "Logs.class.php");
require_once(INC_DIR . "Browser.class.php");

error_reporting(E_ALL);

$logs = new Logs();

$url = "https://loan-v3.mbk.ru/";

try {
    $browser = new Browser("socks5://127.0.0.1:9050");
    do {
        $browser->request("{$url}", "GET");
        $formRows   = $browser->getForms();

        $formRows["send.php"]["GET"]["amount"] = 1;
        $formRows["send.php"]["GET"]["name"]  = "Уберите мой Номер 7-903-123-4565 с этой формы";
        $formRows["send.php"]["GET"]["phone"] = "+7 903 123 45 65";

    //    reclama_url=&utmsource=&utmmedium=&utmkeyid=&utmadid=&utmkms=&utmkey=&utmcontent=&calc%5Bprice%5D=300+000+&calc%5Btime%5D=72+&name=%D0%A3%D0%B1%D0%B5%D1%80%D0%B8%D1%82%D0%B5+%D0%BC%D0%BE%D0%B9+%D0%BD%D0%BE%D0%BC%D0%B5%D1%80+7-903-123-4565+%D1%81+%D1%8D%D1%82%D0%BE%D0%B9+%D0%B2%D0%B0%D1%82%D0%B5%D1%80%D0%BC%D0%B0%D1%80%D0%BA%D0%B8&phone=%2B7+903+123+45+65&action=send&roistat=2808732&k50sid=74b72464f84d945f&k50uuid=442fd38386f29ee4&landing=https%3A%2F%2Floan-v3.mbk.ru%2F%3Fyagla%3D48562078&roistat=2808732&k50sid=74b72464f84d945f&k50uuid=442fd38386f29ee4&landing=https%3A%2F%2Floan-v3.mbk.ru%2F%3Fyagla%3D48562078
//        $browser->request("https://loan-v3.mbk.ru/ajax/sendConfirmCode.php", "POST", [], "reclama_url=&utmsource=&utmmedium=&utmkeyid=&utmadid=&utmkms=&utmkey=&utmcontent=&calc%5Bprice%5D=300+000+&calc%5Btime%5D=72+&name=%D0%A3%D0%B1%D0%B5%D1%80%D0%B8%D1%82%D0%B5+%D0%BC%D0%BE%D0%B9+%D0%BD%D0%BE%D0%BC%D0%B5%D1%80+7-903-123-4565+%D1%81+%D1%8D%D1%82%D0%BE%D0%B9+%D0%B2%D0%B0%D1%82%D0%B5%D1%80%D0%BC%D0%B0%D1%80%D0%BA%D0%B8&phone=%2B7+903+123+45+65&action=send&roistat=2808732&k50sid=74b72464f84d945f&k50uuid=442fd38386f29ee4&landing=https%3A%2F%2Floan-v3.mbk.ru%2F%3Fyagla%3D48562078&roistat=2808732&k50sid=74b72464f84d945f&k50uuid=442fd38386f29ee4&landing=https%3A%2F%2Floan-v3.mbk.ru%2F%3Fyagla%3D48562078"); //$formRows["send.php"]["GET"]);
        $browser->request("{$url}send.php", "POST", [], "reclama_url=&utmsource=&utmmedium=&utmkeyid=&utmadid=&utmkms=&utmkey=&utmcontent=&calc%5Bprice%5D=300+000+&calc%5Btime%5D=72+&name=%D0%A3%D0%B1%D0%B5%D1%80%D0%B8%D1%82%D0%B5+%D0%BC%D0%BE%D0%B9+%D0%BD%D0%BE%D0%BC%D0%B5%D1%80+7-903-123-4565+%D1%81+%D1%8D%D1%82%D0%BE%D0%B9+%D0%B2%D0%B0%D1%82%D0%B5%D1%80%D0%BC%D0%B0%D1%80%D0%BA%D0%B8&phone=%2B7+903+123+45+65&action=send&roistat=2808732&k50sid=74b72464f84d945f&k50uuid=442fd38386f29ee4&landing=https%3A%2F%2Floan-v3.mbk.ru%2F%3Fyagla%3D48562078&roistat=2808732&k50sid=74b72464f84d945f&k50uuid=442fd38386f29ee4&landing=https%3A%2F%2Floan-v3.mbk.ru%2F%3Fyagla%3D48562078"); //$formRows["send.php"]["GET"]);
//        var_dump($browser->res);
        $browser->request("{$url}/ajax/sendConfirmCode.php", "POST", [], "reclama_url=&utmsource=&utmmedium=&utmkeyid=&utmadid=&utmkms=&utmkey=&utmcontent=&calc%5Bprice%5D=300+000+&calc%5Btime%5D=72+&name=%D0%A3%D0%B1%D0%B5%D1%80%D0%B8%D1%82%D0%B5+%D0%BC%D0%BE%D0%B9+%D0%BD%D0%BE%D0%BC%D0%B5%D1%80+7-903-123-4565+%D1%81+%D1%8D%D1%82%D0%BE%D0%B9+%D0%B2%D0%B0%D1%82%D0%B5%D1%80%D0%BC%D0%B0%D1%80%D0%BA%D0%B8&phone=%2B7+903+123+45+65&action=send&roistat=2808732&k50sid=74b72464f84d945f&k50uuid=442fd38386f29ee4&landing=https%3A%2F%2Floan-v3.mbk.ru%2F%3Fyagla%3D48562078&roistat=2808732&k50sid=74b72464f84d945f&k50uuid=442fd38386f29ee4&landing=https%3A%2F%2Floan-v3.mbk.ru%2F%3Fyagla%3D48562078"); //$formRows["send.php"]["GET"]);
//        $browser->request("{$url}send.php", "POST", [], "reclama_url=&utmsource=&utmmedium=&utmkeyid=&utmadid=&utmkms=&utmkey=&utmcontent=&calc%5Bprice%5D=300+000+&calc%5Btime%5D=72+&name=%D0%A3%D0%B1%D0%B5%D1%80%D0%B8%D1%82%D0%B5+%D0%BC%D0%BE%D0%B9+%D0%BD%D0%BE%D0%BC%D0%B5%D1%80+7-903-123-4565+%D1%81+%D1%8D%D1%82%D0%BE%D0%B9+%D0%B2%D0%B0%D1%82%D0%B5%D1%80%D0%BC%D0%B0%D1%80%D0%BA%D0%B8&phone=%2B7+903+123+45+65&action=send&roistat=2808732&k50sid=74b72464f84d945f&k50uuid=442fd38386f29ee4&landing=https%3A%2F%2Floan-v3.mbk.ru%2F%3Fyagla%3D48562078&roistat=2808732&k50sid=74b72464f84d945f&k50uuid=442fd38386f29ee4&landing=https%3A%2F%2Floan-v3.mbk.ru%2F%3Fyagla%3D48562078"); //$formRows["send.php"]["GET"]);
//        var_dump($browser->res);
    } while (1 == 1);
} catch (Exception $e) {
    var_dump($e);
    $logs->add($e);
}
