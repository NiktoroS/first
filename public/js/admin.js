function objAjax(module, method, post, id, async, att) {
    var result = true;
    if (true != async) {
        async = false;
    }
    if (!id) {
        id = "objAjax";
    }

    $("#" + id).html('Подождите, идет загрузка... <img src="/img/wait.gif"/> <a href="javascript:location.reload();">обновить</a>');
    $.ajax({
        type: "POST",
        async: async,
        url: "/ajax",
        dataType: "html",
        data: "module=" + module + "&method=" + method + "&" + post + "&X-CSRF-Token=" + $("meta[name='X-CSRF-Token']").attr("content"),
        success: function(html) {
            $("#" + id).html(html);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            result = false;
            if ("Unauthorized" == errorThrown) {
                document.location.replace("/auth");
                return result;
            }
            if (!att) {
                att = 0;
            }
            att ++;
            if (att < 2) {
                objAjax(module, method, post, id, async, att);
            }
        },
    });
    if ($("#systemError") && $("#systemError").val()) {
//        alert("СИСТЕМНАЯ ОШИБКА !!! СООБЩИТЕ РАЗРАБОТЧИКУ:\n" + $("#systemError").val());
        $("#systemError").val("");
        result = false;
    }
    if ($("#userError") && "200" == $("#userError").prop("name") && "" != $("#userError").val()) {
        alert($("#userError").val());
        $("#userError").val("");
        result = false;
    }
    return result;
}

function saveVal(table, id, field, val) {
    return objAjax("admin", "saveVal", "table=" + table + "&id=" + id + "&field=" + field + "&val=" + encodeURIComponent(val));
}

function showTable(offset) {
    if (!$("#table").val()) {
        return false;
    }
    var params = "table=" + $("#table").val() + "&name=" + $("#search_name").val();
    if ($("#orderBy").val()) {
        params = params + "&orderBy=" + $("#orderBy").val();
    }
    if ($("#orderType").val()) {
        params = params + "&orderType=" + $("#orderType").val();
    }
    if ($("#limit").val()) {
        params = params + "&limit=" + $("#limit").val();
    }
    if (offset) {
        params = params + "&offset=" + offset;
    }
    objAjax("admin", "showTable", params, "result");
}

function sortTableCur(orderBy, orderType) {
    $("#orderBy").val(orderBy);
    if ("ASC" == orderType) {
        $("#orderType").val("DESC");
    } else {
        $("#orderType").val("ASC");
    }
    showTable();
}

function editTable(id) {
    objAjax("admin", "editTable", "table=" + $("#table").val() + "&id=" + id, "modalBody");
    if (!id) {
        showTable();
    }
}

function saveTable(id, field, val) {
    saveVal($("#table").val(), id, field, val);
    showTable();
}

function deleteTable(table, id, name) {
    var confirmText = "Действительно удалить";
    if (name) {
        confirmText = confirmText + ": " + name;
    }
    if (myConfirm(confirmText)) {
        objAjax("admin", "deleteTable", "table=" + table + "&id=" + id);
    }
}

function myConfirm(confirmText) {
    return confirm(confirmText);
}

function hex2bin(hex)
{
    var bytes = [], str;

    for(var i = 0; i < hex.length - 1; i += 2)
        bytes.push(parseInt(hex.substr(i, 2), 16));

    return String.fromCharCode.apply(String, bytes);    
}