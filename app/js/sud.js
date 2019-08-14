var number = "";

function setEmpty()
{
    number = "";
}

function setNumber(_this)
{
    number = jQuery(_this).html();
    $("td[id^='set_']").attr("bgcolor", "white");
    $("td[id^='set_" + number + "']").attr("bgcolor", "green");
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
    for (var r = 0; r < 9; r ++) {
        for (var c = 0; c < 9; c ++) {
            rows[r * 9 + c] = jQuery("#game_" + r + "_" + c).html();
        }
    }
    json = objAjaxJson("sud", "solve", "rows=" + rows, "result");
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
}
