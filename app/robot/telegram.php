<?php
use app\inc\Lock;
use app\inc\Logs;
use app\inc\Solver;
use app\inc\SudokuSolver;
use app\inc\TelegramClass;
use app\inc\WaterSolver;

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
require_once(INC_DIR . "Solver.php");
require_once(INC_DIR . "SudokuSolver.php");
require_once(INC_DIR . "TelegramClass.php");
require_once(INC_DIR . "WaterSolver.php");

error_reporting(E_ALL);

$lock = new Lock();
$logs = new Logs();

if (false === $lock->setLock()) {
    $logs->add("error in set lock");
    exit;
}

try {
    $telegram   = new TelegramClass();
    $ws         = new WaterSolver();
    $offset     = 0;
    do {
        $updates = $telegram->getUpdates($offset);
        if (!is_array($updates)) {
            var_dump($updates);
            sleep(120);
            continue;
        }
        foreach ($updates as $update) {
            $level      = null;
            $cntColors  = null;
            if ($update->message->date + 360000 < time()) {
                continue;
            }
            if (!$update->message->photo) {
                continue;
            }
            if (!empty($update->message->caption)) {
                $row = explode(" ", $update->message->caption);
                if (isset($row[0])) {
                    $level = intval($row[0]);
                }
                if (isset($row[1])) {
                    $cntColors = intval($row[1]);
                } else if ($level > 1300 && $level < 3000) {
                    $cntColors = 15;
                }
            }
            foreach ($update->message->photo as $photo) {
                if (1280 != $photo->height) {
                    continue;
                }
                $fileInfo = $telegram->getFile($photo->file_id);
                $tmpFile  = TMP_DIR . $photo->file_id;
                file_put_contents($tmpFile, $telegram->downloadFile($fileInfo->file_path));
                switch ($photo->width) {
                    case 582:
                        $gadget = "x9d";
                        break;

                    default:
                        $gadget = "Mi11";
                        break;
                }
                if ($cntColors) {
                    $solver = [
                        "cntColors" => $cntColors,
                        "level" => $level,
                        "type"  => "Water"
                    ];
                } else if ($level) {
                    $solver = [
                        "level" => $level,
                        "type"  => "Sudoku"
                    ];
                } else {
                    $solver = Solver::getSolverFromFile($tmpFile, $gadget);
                }
                switch ($solver["type"]) {
                    case "Sudoku":
                        if (!$level) {
                            $level = SudokuSolver::getLevelFromFile($tmpFile);
                        }
                        $rows = SudokuSolver::getRowsFromFile($tmpFile);
                        $resultRows = SudokuSolver::solve($rows);
                        $telegram->sendDocument(
                            TMP_DIR . SudokuSolver::saveAc($resultRows, $rows, $solver["level"], $gadget) . ".txt",
                            $update->message->chat->id
                        );
                        if (!empty($update->message->caption)) {
                            foreach (["Mi11", "Pad6", "x9d"] as $_gadget) {
                                if  ($_gadget == $gadget) {
                                    continue;
                                }
                                $telegram->sendDocument(
                                    TMP_DIR . SudokuSolver::saveAc($resultRows, $rows, $solver["level"], $_gadget) . ".txt",
                                    $update->message->chat->id
                                );
                            }
                        }
                        break;

                    case "Water":
                        $data = $ws->setData($solver["cntColors"], $solver["level"]);
                        $ws->setBottlesFromFile($tmpFile, $data["colors"]);
                        $resultMoves = [];
                        for ($att = 0; $att < 5; $att ++) {
                            $result  = $ws->solve(true);
                            if (!$result["success"]) {
                                continue;
                            }
                            $deley   = sprintf("%d.%d", $result["delay"], $att);
                            $resultMoves[$deley] = $result;
                        }
                        if (!$resultMoves) {
                            break;
                        }
                        ksort($resultMoves);
                        $logs->add("cnt: " . var_export(array_keys($resultMoves), true));
                        $result = array_shift($resultMoves);
                        $ws->saveMovies($result);
                        $telegram->sendDocument(TMP_DIR . $ws->saveAcc() . ".txt", $update->message->chat->id);
                        break;

                    default:
                        break;
                }
            }
            $offset = $update->update_id + 1;
        }
        sleep(15);
    } while (1 == 1);
} catch (Exception $exception) {
    echo($exception->getMessage());
    $logs->add($exception);
}

$lock->delLock();