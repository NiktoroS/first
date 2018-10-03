<?php

/**
 * @category
 * @author <first@mail.ru>
 * @since  2018-03-30
 */

class MySmarty extends Smarty
{

    public function __construct()
    {
        parent::__construct();
        $this->debugging = false;
        $this->caching = false;
        $this->addPluginsDir(INC_DIR . "mySmarty/");
        $this->setTemplateDir(TPL_DIR);
        $this->setCacheDir(TMP_DIR . "cache");
        $this->setCompileDir(TMP_DIR . "cache/templates");

        if (isset($_SERVER["HTTP_HOST"]) and in_array($_SERVER["HTTP_HOST"], array ("vintage.service.vm"))) {
            exec("rm " . $this->compile_dir . "/*");
        }
    }

    public function fetch($template = NULL, $cacheId = NULL, $compileId = NULL, $parent = NULL, $display = false, $mergeTplVars = true, $noOutputFilter = false)
    {
        if (!empty($_SESSION["user"]["css"]["tpl"]) and is_file($this->template_dir[0] . $_SESSION["user"]["css"]["tpl"] . $template)) {
            $template = $_SESSION["user"]["css"]["tpl"] . $template;
        }
        return preg_replace(array ('/ +/', '/\>\s+\</'), array (" ", "><"), parent::fetch($template , $cacheId, $compileId, $parent, $display, $mergeTplVars, $noOutputFilter));
    }
}