
var number = "";
var idCell = "";

function setCell(_this)
{
    idCell = _this.id;
    _this.style.backgroundColor = "green";
}

function setNumber(_number)
{
    number = _number;
    var cell = document.getElementById(idCell);
    cell.innerHTML = number;
    cell.style.backgroundColor = "black";
}

function putNumber(_this)
{
    _this.innerHTM = number;
}

function clearAll()
{
    for (var r = 0; r < 9; r ++) {
        for (var c = 0; c < 9; c ++) {
            document.getElementById("game_" + r + "_" + c).innerHTML = "";
        }
    }
}

function solveAll()
{
    var rows = [];
    var val;
    var s = "";
    for (var r = 0; r < 9; r ++) {
        rows[r] = [];
        for (var c = 0; c < 9; c ++) {
            val = parseInt(document.getElementById("game_" + r + "_" + c).innerHTML, 10);
            if (!val) {
                val = 0;
            }
            rows[r][c] = val;
            s = s + "" + val;
        }
    }
    var time_beg = new Date().getTime();

    var solver   = new SudokuSolver();

    rows = solver.solve(s, { result: "chunks" });

    var time = (new Date().getTime() - time_beg) / 1000.0;

    document.getElementById("time").innerHTML = time;
/*
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
*/
    for (var r = 0; r < 9; r ++) {
        for (var c = 0; c < 9; c ++) {
            var val = rows[r][c];
            if (!val) {
                val = "";
            }
            if ("" != document.getElementById("game_" + r + "_" + c).innerHTML) {
                val = "<b>" + val + "</b>";
            }
            document.getElementById("game_" + r + "_" + c).innerHTML = val;
        }
    }
}

function SudokuSolver()
{

    var puzzle_table = [];
    /*
    * Check if the number is a legal candidate
    * for the given cell (by Sudoku rules).
    */
    function check_candidate(num, row, col)
    {
        for (var i = 0; i < 9; i++) {
            var b_index = ((Math.floor(row / 3) * 3) + Math.floor(i / 3)) * 9 + (Math.floor(col / 3) * 3) + (i % 3);
            if (num == puzzle_table[(row * 9) + i] || num == puzzle_table[col + (i * 9)] || num == puzzle_table[b_index]) {
                return false;
            }
        }
        return true;
    }
    /*
    * Recursively test all possible numbers for a given cell until
    * the puzzle is solved.
    */
    function get_candidate(index)
    {
        if (index >= puzzle_table.length) {
            return true;
        } else if (puzzle_table[index] != 0) {
            return get_candidate(index + 1);
        }

        for (var i = 1; i <= 9; i++) {
            if (check_candidate(i, Math.floor(index / 9), index % 9)) {
                puzzle_table[index] = i;
                if (get_candidate(index + 1)) {
                    return true;
                }
            }
        }

        puzzle_table[index] = 0;
        return false;
    }
    /*
    * Split result of puzzle into chunks by 9.
    */
    function chunk_in_groups(arr) {
        var result = [];
        for (var i = 0; i < arr.length; i += 9) {
            result.push(arr.slice(i, i + 9));
        }
        return result;
    }
    /*
    * Start solving the game for provided puzzle and options.
    */
    this.solve = function (puzzle, options) {
        options = options || {};
        var result = options.result || 'string';
        puzzle_table = puzzle.split('').map(function (v) {
            return isNaN(v) ? 0 : +v
        });

        if (puzzle.length !== 81) {
            return 'Puzzle is not valid.'
        }

        return !get_candidate(0) ? 'No solution found.' : result === 'chunks' ? chunk_in_groups(puzzle_table) : result === 'array' ? puzzle_table : puzzle_table.join('');
    }
}

/*
function sudoku(puzzle)
{
    while (!isSolved(puzzle)) {
        for (x = 0; x < 9; x++) {
            for (y = 0; y < 9; y++) {
                puzzle[y][x] = digit(puzzle, x, y);
            }
        }
    }
    return puzzle;
}

function digit(puzzle, x, y)
{
    if (puzzle[y][x] !== 0) return puzzle[y][x];

    var row = puzzle[y];
    var column = columnArray(puzzle, x);
    var grid = gridArray(puzzle, x, y);

    var knowns = row.concat(column, grid);

    var possibilities = [1, 2, 3, 4, 5, 6, 7, 8, 9].filter(function(item) { return knowns.indexOf(item) === -1; });

    return possibilities.length == 1 ? possibilities[0] : 0;
}

function columnArray(puzzle, idx)
{
    return puzzle.map(function(row) { return row[idx]; });
}

function gridArray(puzzle, x, y)
{
    x = Math.floor(x / 3) * 3;
    y = Math.floor(y / 3) * 3;

    var arr = [];

    for (i = x; i < x + 3; i++) {
        for (j = y; j < y + 3; j++) {
            arr.push(puzzle[j][i]);
        }
    }

    return arr;
}

function sum(arr)
{
    return arr.reduce(function(a, n) { return a + n; }, 0);
}

function winningRow(arr)
{
    return sum(arr.map(function(num) { return Math.pow(2, num - 1); })) == 511;
}

function isSolved(puzzle)
{
    return puzzle.filter(function (row) { return !winningRow(row); }).length === 0;
}








/*




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

*/