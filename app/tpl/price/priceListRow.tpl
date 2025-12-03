<h5>
    {$priceListRow.name} действителен до {date("d.m.Y", strtotime($priceListRow.date))}
</h5>
<input type="hidden" id="name_price_List" value="{$priceListRow.name} {date("d.m.Y", strtotime($priceListRow.date))}">
<input type="hidden" id="id_price_list" value="{$priceListRow.id}">

<input type="button" class="btn btn-outline-success" onClick="addPriceItem('priceListRow', 'id={$priceListRow.id}')" value="Добавить"/>

<table class="table table-striped table-bordered" style="margin-bottom: 0">
<thead>
<tr>
    <th scope="col">№</th>
    <th scope="col">Артикул</th>
    <th scope="col">Название</th>
    <th scope="col">Стоимость</th>
    <th scope="col">Валюта</th>
    <th scope="col"></th>
</tr>
</thead>
<tbody>
<tr id="tr_new" style="display: none;">
    <td colspan="3">
        {html_options name="id_item" id="new_id_item" options=$itemRows}
    </td>
    <td>
        <input id="new_price" type="text" size="5" value=""/>
    </td>
    <td>{$priceListRow.currency}</td>
    <td>
        <input type="button" class="btn btn-outline-success" onClick="savePriceItem('new', '{$priceListRow.id}')" value="Сохранить"/>
    </td>
</tr>
{foreach $priceListRow.priceItemRows as $priceItemId => $priceItemRow}
<tr>
    <td>{$priceItemId + 1}</td>
    <td>{$priceItemRow.itemRow.article}</td>
    <td>{$priceItemRow.itemRow.name}</td>
    <td>
        <span id="{$priceItemRow.id}_view">
            {number_format($priceItemRow.price, 2, ".", "")}
        </span>
        <input id="{$priceItemRow.id}_edit" type="text" size="5" value="{number_format($priceItemRow.price, 2, ".", "")}" style="display: none;"/>
    </td>
    <td>{$priceListRow.currency}</td>
    <td>
        <span id="{$priceItemRow.id}_view_btn">
            <input type="button" class="btn btn-outline-success" onClick="editPriceItem('{$priceItemRow.id}')" value="Изменить"/>
            <input type="button" class="btn btn-outline-danger"  onClick="deletePriceItem('{$priceItemRow.id}')" value="Удалить"/>
        </span>
        <span id="{$priceItemRow.id}_edit_btn" style="display: none;">
            <input type="button" class="btn btn-outline-success" onClick="savePriceItem('{$priceItemRow.id}', '{$priceListRow.id}')" value="Сохранить"/>
        </span>
    </td>
</tr>
{/foreach}
</tbody>
</table>
<script>
$(document).ready(function() {
    console.log($("#name_price_list").val());
    $("#modalTitle").html($("#name_price_list").val()));
});
</script>