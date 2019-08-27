jQuery(document).ready(function() {
//    jQuery("#game_0_0").html(1);
//    jQuery("#game_0_1").html(2);
//    jQuery("#game_0_2").html(3);
});

var number = "";
var idCell = "";
var iii    = 0;

function setEmpty()
{
    number = "";
    $("#" + idCell).html(number);
    $("#" + idCell).attr("bgcolor", "black");
}

function setCell(_this)
{
    idCell = _this.id;
    $("#" + idCell).attr("bgcolor", "green");
}

function setNumber(_this)
{
    number = jQuery(_this).html();
    $("#" + idCell).html(number);
    $("#" + idCell).attr("bgcolor", "black");
}

function putNumber(_this)
{
    jQuery(_this).html(number);
}

function clearAll()
{
    for (var r = 0; r < 9; r ++) {
        for (var c = 0; c < 9; c ++) {
            jQuery("#game_" + r + "_" + c).html("");
        }
    }
}

function solveAll()
{
    var rows = [];
    var val;
    for (var r = 0; r < 9; r ++) {
        rows[r] = [];
        for (var c = 0; c < 9; c ++) {
            val = parseInt(jQuery("#game_" + r + "_" + c).html());
            if (!val) {
                val = 0;
            }
            rows[r][c] = val;
        }
    }
    json = objAjaxJson("sud", "solve", "rows=" + rows);
//    json = false;
    if (json) {
        jQuery.each(json.rows, function(r, row) {
            jQuery.each(row, function(c, val) {
                if (!val) {
                    val = "";
                    if (json.pRows[r]) {
                        jQuery.each(json.pRows[r][c], function(val1, val2) {
                            if (val) {
                                val = val + " ";
                            }
                            val = val + "<span style='font-size:7px;'>" + val2 + "</span>";
                        });
                    }
                }
                if ("" != jQuery("#game_" + r + "_" + c).html()) {
                    val = "<b>" + val + "</b>";
                }
                jQuery("#game_" + r + "_" + c).html(val);
            });
        });
        jQuery.each(json, function(key, val) {
            jQuery("#" + key).html(val);
        });
        return;
    }

    solveHelper(rows);

    jQuery.each(rows, function(r, row) {
        jQuery.each(row, function(c, val) {
            if (!val) {
                val = "";
            }
            if ("" != jQuery("#game_" + r + "_" + c).html()) {
                val = "<b>" + val + "</b>";
            }
            jQuery("#game_" + r + "_" + c).html(val);
        });
    });
}

function solveHelper(solution)
{
    iii = iii + 1;

    console.log(iii);
    console.log(solution);

    var minRow     = -1;
    var minColumn  = -1;

    var minValues  = [];

    while (true) {
        minRow = -1;
        for (var rowIndex = 0; rowIndex < 9; rowIndex ++) {
            for (var columnIndex = 0; columnIndex < 9; columnIndex ++) {
                if (solution[rowIndex][columnIndex] != 0) {
                    continue;
                }
                var possibleValues = findPossibleValues(rowIndex, columnIndex, solution);

                if (possibleValues.length == 0) {
                    return false;
                }
                if (possibleValues.length == 1) {
                    solution[rowIndex][columnIndex] = possibleValues.pop();
                }
                if (minRow < 0 || possibleValues.length < minValues.length) {
                    minRow     = rowIndex;
                    minColumn  = columnIndex;

                    minValues  = possibleValues;
                }
            }
        }
        if (minRow == -1) {
            return true;
        } else if (minValues.length > 1) {
            break;
        }
    }

    jQuery.each(minValues, function(key, v) {
        var solutionCopy = [];
        solutionCopy = solution;
        solutionCopy[minRow][minColumn] = v;

        if (solveHelper(solutionCopy)) {
            solution = solutionCopy;
            return true;
        }
    });

    return false;
}

function diffAjax()
{
    json = objAjaxJson("sud", "array_diff", "a=" + jQuery("#a").val() + "&b=" + jQuery("#b").val());
    jQuery("#c").val(json.c);
}

function diffJs()
{
    var a = jQuery("#a").val();
    var b = jQuery("#b").val();
    c = array_diff(a.split(","), b.split(","))
    jQuery("#c").val(c.join(","));
}

function findPossibleValues(rowIndex, columnIndex, puzzle)
{
    var values = [1, 2, 3, 4, 5, 6, 7, 8, 9]; //range(1, 9);

    values = array_diff(values, getRowValues(rowIndex, puzzle));
    values = array_diff(values, getColumnValues(columnIndex, puzzle));
    values = array_diff(values, getBlockValues(rowIndex, columnIndex, puzzle));

    return values;
}

function getRowValues(rowIndex, puzzle)
{
    var values = puzzle[rowIndex];
    return values;
}

function getColumnValues(columnIndex, puzzle)
{
    var values = [];
    for (var r = 0; r < 9; ++r) {
        values[r] = puzzle[r][columnIndex];
    }
    return values;
}

function getBlockValues(rowIndex, columnIndex, puzzle)
{
    var values = [];
    blockRowStart      = 3 * Math.floor(rowIndex / 3);
    blockColumnStart   = 3 * Math.floor(columnIndex / 3);
    for (var r = 0; r < 3; ++r) {
        for (var c = 0; c < 3; ++c) {
            values[r * 3 + c] = puzzle[blockRowStart + r][blockColumnStart + c];
        }
    }
    return values;
}

function array_diff(a1, a2)
{
    var diff = [];

    for (var i = 0; i < a1.length; ++i) {
        var coincide = false;
        for (var j = 0; j < a2.length; ++j) {
            if (a1[i] == a2[j]) {
                coincide = true;
                break;
            }
        }
        if (false == coincide) {
            diff.push(a1[i]);
        }
    }
    return diff;
}

