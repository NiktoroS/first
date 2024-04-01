<?php

namespace app\inc;

/**
 * @category Логирование
 * @author <first@mailru>
 * @since  2018-03-30
 */

class Logs
{

    private $file, $copy;

    public function __construct($name = false)
    {
        if (!$name) {
            $dirRows = explode(DS, $_SERVER["SCRIPT_NAME"]);
            $name   = array_pop($dirRows);
            $name   = array_pop($dirRows) . "_" . $name;
        }
        if (!is_dir(LOG_DIR)) {
            mkdir(LOG_DIR, 0777, true);
        }
        $this->file = LOG_DIR . $name . ".log";
    }

    public function setCopy($copy)
    {
        $this->copy = $copy;
    }

    public function add($var)
    {
        file_put_contents($this->file, date("[Y-m-d H:i:s]") . (isset($this->copy) ? sprintf("[%04d] ", $this->copy) : " ") . strval($var) . "\n", FILE_APPEND);
    }
}