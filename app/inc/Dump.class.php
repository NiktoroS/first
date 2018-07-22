<?php

// =============================
// _dump();
// Версия 3.1
// Дампит переменные\массивы\объекты,
// а так же по дефолту выводит ошибку MySQL, если таковая присутствует.
// Синтаксис: _dump([mix a][,mix n]);
// Запуск без парамертров аналогичен _dump($GLOBALS);
// Глобальная переменная $DUMP_HIDE в значении TRUE
// включает скрытие вложенных массивов и объектов
// Переменная $DUMP_NOECHO в значении TRUE
// возвращает результат дампа в строке, не выводя его в браузер
function _dump()
{
    if (empty($_SESSION["user"]["id"]) or 1 != $_SESSION["user"]["id"]) {
        // return false;
    }

    global $DUMP_NOECHO;

    $ver = "Dump";

    $bg = false;

    if (count($f = func_get_args())) {
        $out = _dump_thead($ver, 0, 1);
        foreach ($f as $v) {
            $out .= _dump_val($v, $bg = ! $bg);
        }
    } else {
        _dump($GLOBALS);
    }
    $out .= _dump_tfoot();
    if ($DUMP_NOECHO) {
        return $out;
    } else {
        echo $out;
    }
    return true;
}

function _dump_tfoot()
{
    return "</table></td></tr></table>\n";
}

function _dump_val($val, $bg = true, $key = false, $level = 0)
{
    global $DUMP_HIDE;
    static $con = 0;

    $bg = $bg ? "#FFFFFF" : "#F0F0F7";
    $out = "<tr valign=top><td bgcolor=" . $bg . "><nobr>";
    $td = "</nobr></td>";
    if ($key !== false) {
        $td .= "<td bgcolor=" . $bg . ">";
        if (intval($key) - 10000000 < time() && intval($key) + 10000000 > time()) {
            $td .= date("Y-m-d H:i", $key);
        } else {
            $td .= htmlspecialchars($key);
        }
        $td .= "</td>";
    } else {
        $td .= "";
    }
    $td .= "<td bgcolor=" . $bg . " width=100%>";
    if (is_array($val)) {
        $out .= _dump_gettype($val) . $td;
        if (empty($val)) {
            $out .= "[ EMPTY ARRAY ]";
        } else {
            $bbg = false;
            $out .= _dump_thead("show/hide", 1, ($DUMP_HIDE || $level ? 0 : 1), 1);
            foreach ($val as $k => $v) {
                if ($k !== "GLOBALS") {
                    $out .= _dump_val($v, $bbg = ! $bbg, $k, 1);
                }
            }
            $out .= _dump_tfoot();
        }
    } else if (is_object($val)) {
        $out .= _dump_gettype($val) . $td;
        $out .= _dump_thead(get_class($val) . (get_parent_class($val) ? " (" . get_parent_class($val) . ")" : ""), 1, ($DUMP_HIDE ? 0 : 1), 1);
        $bbbg = false;
        if (is_array($f = get_object_vars($val))) {
            foreach ($f as $k => $v) {
                $out .= _dump_val($v, $bbbg = ! $bbbg, $k, 1);
            }
        }
        if (is_array($f = get_class_methods($val))) {
            $con ++;
            $out .= "<tr><td align=center colspan=3  style='height:20px;background:#E0E0E0;cursor:hand' onclick=\"dmet" . $con . ".style.display=(dmet" . $con . ".style.display=='block'?'none':'block');\" onmouseover=\"style.background='#D2D2FF';\" onmouseout=\"style.background='#E0E0E0';\"><b>Methods</b></td></tr>\n" . "<tr><td colspan=3 style='display:none' bgcolor=#EFEFEF id='dmet" . $con . "'>\n" . "<table border=0 width=100% height=100% cellspacing=1 bgcolor=#000000 cellpadding=2>";
            $bbbg = false;
            foreach ($f as $v) {
                $out .= "<tr><td colspan=3 bgcolor=#" . (($bbbg = ! $bbbg) ? "FFFFFF" : "F5F5F5") . ">" . $v . "()</td></tr>";
            }
            $out .= "</table></td></tr>";
        }
        $out .= _dump_tfoot();
    } else {
        $out .= _dump_gettype($val) . $td . _dump_getval($val);
    }
    return $out . "</td></tr>\n";
}

function _dump_thead($name, $num, $opened, $width = '')
{
    static $con = 0;
    $con ++;
    $width = ($width ? "width=100%" : "");
    $opened = ($opened ? "block" : "none");
    $num = ($num ? "<td bgcolor=#EFEFEF><b>Key</b></td>" : "");
    $span = ($num ? 3 : 2);
    $out = "\n<table $width border=0 cellspacing=1 cellpadding=0 bgcolor=#000000 style='font-family:Verdana;font-size:11;color:#000000;font-weight:normal;'>";
    $out .= "\n<tr align=center><td style='height:18px;background:#E0E0E0;cursor:hand' onclick=\"dtr$con.style.display=(dtr$con.style.display=='block'?'none':'block');\" onmouseover=\"style.background='#D2D2FF';\" onmouseout=\"style.background='#E0E0E0';\"><nobr><b>&nbsp;:: $name ::&nbsp;</b></nobr></td></tr>";
    $out .= "\n<tr><td style='display:$opened' bgcolor=#EFEFEF id='dtr$con'>";
    $out .= "\n<table border=0 width=100% height=100% cellspacing=1 bgcolor=#000000 cellpadding=2>";
    $out .= "\n<tr align=center><td bgcolor=#EFEFEF><b>Type</b></td>" . $num . "<td bgcolor=#EFEFEF><b>Value</b></td></tr>";
    return $out;
}

function _dump_getval($m)
{
    if (! isset($m)) {
        $m = "[ Unsetted ]";
    } elseif ($m === true) {
        $m = "[ True ]";
    } elseif ($m === false) {
        $m = "[ False ]";
    } elseif ($m === "") {
        $m = "[ Empty string ]";
    } elseif (is_resource($m)) {
        $m = (string) $m;
    } elseif (! is_float($m) && intval($m) - 10000000 < time() && intval($m) + 10000000 > time()) {
        $m = date("Y-m-d H:i:s", $m) . "[" . $m . "]";
    }
    return nl2br(htmlspecialchars(wordwrap($m, 75, "\n", 1)));
}

function _dump_gettype($m)
{
    if (is_array($m)) {
        $m = "Array(" . count($m) . ")";
    } elseif (is_object($m)) {
        $m = "Object(" . count(get_object_vars($m)) . ")";
    } elseif (is_string($m)) {
        $m = "String(" . strlen($m) . ")";
    } elseif (is_resource($m)) {
        $m = "Resource (" . get_resource_type($m) . ")";
    } else {
        $m = gettype($m);
    }
    return $m;
}

// =========END _dump()========
function _dd($val)
{
    if (_dump($val)) {
        exit();
    }
}
