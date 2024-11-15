<?php
use app\inc\Lock;
use app\inc\Logs;
use app\inc\SudokuSolver;
use app\inc\TelegramClass;

/**
 * @category    robot
 * @package     telegram
 * @author      <andrey.a.sirotkin@megafon.ru>
 * @since       2025-11-14
 */

set_time_limit(0);
ini_set("memory_limit", "4095M");

require_once(dirname(__DIR__) . "/cnf/main.php");
require_once(INC_DIR . "Lock.php");
require_once(INC_DIR . "Logs.php");
require_once(INC_DIR . "SudokuSolver.php");
require_once(INC_DIR . "TelegramClass.php");

error_reporting(E_ALL);

$lock = new Lock();
$logs = new Logs();

if (false === $lock->setLock()) {
    $logs->add("error in set lock");
    exit;
}

try {
    $telegram   = new TelegramClass();
    $offset = 0;
    do {
        foreach ($telegram->getUpdates($offset) as $update) {
            $level = null;
            if ($update->message->caption) {
                $level = $update->message->caption;
            }
            if ($update->message->date + 3600 < time()) {
                continue;
            }
            if (!$update->message->photo) {
                continue;
            }
            foreach ($update->message->photo as $photo) {
                if (1280 != $photo->height) {
                    continue;
                }
                $fileInfo = $telegram->getFile($photo->file_id);
                $tmpFile  = TMP_DIR . $photo->file_id;
                file_put_contents($tmpFile, $telegram->downloadFile($fileInfo->file_path));
                if (preg_match('/\.jpg$/', $fileInfo->file_path)) {
                    $im  = imagecreatefromjpeg($tmpFile);
                } else {
                    $im  = imagecreatefrompng($tmpFile);
                }
                $rows = SudokuSolver::getRowsFromIm($im);
                $resultRows = SudokuSolver::solve($rows);
                $telegram->sendDocument(
                    ROOT_DIR . "content" . DS . SudokuSolver::saveAcc($resultRows, $rows, $level, "Mi11") . ".txt",
                    "",
                    $update->message->chat->id
                );
                $telegram->sendDocument(
                    ROOT_DIR . "content" . DS . SudokuSolver::saveAcc($resultRows, $rows, $level, "Pad6") . ".txt",
                    "",
                    $update->message->chat->id
                );
            }
//            $offset = $update->update_id;
        }
        sleep(60);
    } while (1 == 1);
} catch (Exception $e) {
    $logs->add($e);
}

$lock->delLock();
