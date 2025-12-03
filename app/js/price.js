function priceContent(method, data)
{
    objAjax("price", method, data, "content");
}

function priceModal(method, data)
{
    objAjax("price", method, data, "modalBody");
}

function exportExcel(id)
{
    window.open("/price/priceListRowExport/?id=" + id, "xlsx")
}

function editPriceItem(id)
{
    $("#" + id + "_view").attr("style", "display: none;");
    $("#" + id + "_view_btn").attr("style", "display: none;");
    $("#" + id + "_edit").attr("style", "display: block;");
    $("#" + id + "_edit_btn").attr("style", "display: block;");
}

function savePriceItem(id, idPriceItem)
{
    var data = "id=" + id;
    if ("new" == id) {
        $("#tr_new").attr("style", "display: none;");
        data += "&id_price_list=" + idPriceItem;
        data += "&id_item=" + $("#new_id_item").val();
        data += "&price=" + $("#new_price").val();
    } else {
        data += "&price=" + $("#" + id + "_edit").val();
        $("#" + id + "_view").html($("#" + id + "_edit").val());
        $("#" + id + "_view").attr("style", "display: block;");
        $("#" + id + "_view_btn").attr("style", "display: block;");
        $("#" + id + "_edit").attr("style", "display: none;");
        $("#" + id + "_edit_btn").attr("style", "display: none;");
    }
    var json = objAjaxJson("price", "savePriceItem", data);
    if ("true" == json.success) {
        alert("Позиция сохранена");
    } else {
        alert("Не удалось сохранить позицию");
    }
    priceModal("priceListRow", 'id=' + $("#id_price_list").val());
}

function deletePriceItem(id)
{
    if (!confirm("Действительно удалить?")) {
        return
    }
    var data = "active=false&id=" + id;
    var json = objAjaxJson("price", "savePriceItem", data);
    if ("true" == json.success) {
        alert("Позиция удалена");
    } else {
        alert("Не удалось удалить позицию");
    }
    priceModal("priceListRow", 'id=' + $("#id_price_list").val());
}

function addPriceItem()
{
    $("#tr_new").attr("style", "");
}

$(document).ready(function() {
    priceContent("providerRows");
});