<?php

namespace app\inc;

/**
 * @category лок, для работы офлайн скриптов
 * @author <mail@mail.ru>
 * @since  2018-04-10
 */

class Lock
{
    private $fileName, $copy;
    private $fp;

    public function __construct($name = "")
    {
        if (!$name) {
            $name = basename($_SERVER["SCRIPT_NAME"]);
        }
        $this->fileName = ROOT_DIR . ".lock" . DS . $name;
    }

    public function setLock($copies = 1)
    {
        for ($this->copy = 0; $this->copy < $copies; $this->copy ++) {
            $this->fileNameCopy = sprintf("%s.%03u.lock", $this->fileName, $this->copy);
            $this->fp = fopen($this->fileNameCopy, "w");
            if (flock($this->fp, LOCK_EX + LOCK_NB)) {
                fwrite($this->fp, date("Y-m-d H:i:s"));
                break;
            } else {
                fclose($this->fp);
            }
        }
        return ($this->copy < $copies) ? $this->copy : false;
    }

    public function delLock()
    {
        fclose($this->fp);
        unlink($this->fileNameCopy);
    }
}
