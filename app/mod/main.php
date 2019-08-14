<?php

require_once(INC_DIR . "Tools.class.php");
require_once(INC_DIR . "MySmarty.class.php");
require_once(INC_DIR . "Storage/MySqlStorage.php");

/**
 * @category основной модуль
 * @author <first@mail.ru>
 * @since  2018-03-21
 */

class main extends MySmarty
{
    protected $meta, $storage, $data = [];

    public function __construct()
    {
        parent::__construct();
        $this->startPage();
    }

    public function show($params = [])
    {
        if (isset($params[0])) {
            throw new Exception("not found: " . join("/", $params), 404);
        }
    }

    public function info()
    {
        phpinfo();
        exit;
    }

    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        $this->assignPage();
        parent::display($template, $cache_id, $compile_id, $parent);
    }

    protected function assignPage()
    {
        if (isset($_REQUEST["debug"])) {
            _dump($this->data);
        }
        $this->yesNo();
        $this->assignMeta();
        $this->assign($this->data);
    }

    protected function startPage()
    {
        $this->storage = new MySqlStorage();
    }

    protected function yesNo()
    {
        $this->data["yesNo"] = array ("нет", "да");
    }

    protected function setData($data)
    {
        foreach($data as $key => $val) {
            $this->data[$key] = $val;
        }
    }

    protected function assignMeta()
    {
        $this->data["metaRows"] = array (
            "viewport"      => "width=device-width, initial-scale=1",
            "keywords"      => KEYWORDS,
            "description"   => DESCR,
            "title"         => TITLE,
        );
        if (empty($_SESSION["X-CSRF-Token"])) {
            $_SESSION["X-CSRF-Token"] = md5(uniqid(rand(), true));
        }
        $this->data["metaRows"]["X-CSRF-Token"] = $_SESSION["X-CSRF-Token"];
    }
}