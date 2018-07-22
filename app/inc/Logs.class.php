<?php
/**
 * @category Логирование
 * @author <first@mailru>
 * @since  2018-03-30
 */

class Logs
{

    private $stream, $copy;

    public function __construct($name = false)
    {
        if (!$name) {
            $dirArr = explode(DS, $_SERVER["SCRIPT_NAME"]);
            $name   = array_pop($dirArr);
            $name   = array_pop($dirArr) . "_" . $name;
        }
        if (!is_dir(LOG_DIR)) {
        	mkdir(LOG_DIR);
        }
        $file = LOG_DIR . $name . ".log";
        if (is_file($file)) {
            $this->stream = fopen($file, "a");
        } else {
            $this->stream = fopen($file, "w");
        }
    }

    public function setCopy($copy)
    {
        $this->copy = $copy;
    }

    public function add($var)
    {
        fwrite($this->stream, date("[Y-m-d H:i:s]") . (isset($this->copy) ? sprintf("[%04d] ", $this->copy) : " ") . strval($var) . "\n");
    }

    public function __destruct()
    {
        fclose($this->stream);
    }

}