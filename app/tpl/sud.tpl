<!DOCTYPE HTML><html>
<head>

<style>
body {
    background-color: black;
    color: white;
}
table {
    border-collapse: collapse;
}
td {
    border: 1px solid white;
    padding: 0;
}
</style>

<script type="text/javascript">
var number = " ";
var idCell = "game_0_0";

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
            document.getElementById("game_" + r + "_" + c).innerHTML = " ";
        }
    }
}

function solveAll()
{
    var time_begin = new Date().getTime();

    var s = "";
    for (var r = 0; r < 9; r ++) {
        for (var c = 0; c < 9; c ++) {
            s = s + "" + document.getElementById("game_" + r + "_" + c).innerHTML;
        }
    }

    var rows = new SudokuSolver().solve(s, { result: "chunks" });

    if (Array.isArray(rows)) {
        document.getElementById("error").innerHTML = "";
        for (var r = 0; r < 9; r ++) {
            for (var c = 0; c < 9; c ++) {
                var val = rows[r][c];
                if (" " != document.getElementById("game_" + r + "_" + c).innerHTML) {
                    val = "<b>" + val + "</b>";
                }
                document.getElementById("game_" + r + "_" + c).innerHTML = val;
            }
        }
    } else {
        document.getElementById("error").innerHTML = rows;
    }

    document.getElementById("time").innerHTML = (new Date().getTime() - time_begin) / 1000.0;
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
    function chunk_in_groups(arr)
    {
        var result = [];
        for (var i = 0; i < arr.length; i += 9) {
            result.push(arr.slice(i, i + 9));
        }
        return result;
    }

    /*
    * Start solving the game for provided puzzle and options.
    */
    this.solve = function (puzzle, options)
    {
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
</script>

</head>
<body>
<p id="error"></p>
<table>
<tbody>
<tr valign="top">
    <td style="border: 0;">
        <table>
        <tbody>
{for $r1 = 0; $r1 < 3; $r1++}
        <tr>
{for $c1 = 0; $c1 < 3; $c1++}
            <td>
                <table>
                <tbody>
{for $r2 = 0; $r2 < 3; $r2++}
                <tr>
{for $c2 = 0; $c2 < 3; $c2++}
                    <td id="game_{$r1 * 3 + $r2}_{$c1 * 3 + $c2}" width="25" height="25" align="center" onClick="setCell(this)"> </td>
{/for}
                </tr>
{/for}
                </tbody>
                </table>
                </td>
{/for}
        </tr>
{/for}
        </tbody>
        </table>
    </td>
    <td style="border: 0;">
        <table>
        <tbody class="set">
{for $r = 0; $r < 3; $r++}
        <tr>
{for $c = 0; $c < 3; $c++}
                <td id="set_{$r * 3 + $c + 1}" width="35" height="35" align="center" onClick="setNumber('{$r * 3 + $c + 1}')"><b>{$r * 3 + $c + 1}</b></td>
{/for}
        </tr>
{/for}
        </tbody>
        </table>
        <input type="button" value="E"     onClick="setNumber(' ')"/>
        <input type="button" value="Clear" onClick="clearAll()"/>
        <input type="button" value="Solve" onClick="solveAll()"/>
    </td>
</tr>
</tbody>
</table>

<p id="time"></p>

</body>
</html>