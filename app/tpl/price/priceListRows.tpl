<h5>
    Праийс листы на дату
    <input type="date" value="{date("Y-m-d", strtotime($date))}" onChange="priceContent('priceListRows', '{$requestStr}&newDate=' + this.value)">
</h5>

<div class="scroll-table">
    <table class="table table-striped table-bordered" style="margin-bottom: 0">
    <thead>
    <tr>
        <th scope="col" width="50">№</th>
        <th scope="col" width="500">Название</th>
        <th scope="col" width="500">Поствщик</th>
        <th scope="col" width="100">Валюта</th>
        <th scope="col" width="200">Cрок действия</th>
        <th scope="col" width="200"></th>
    </tr>
    </thead>
    </table>
    <div class="scroll-table-body">
        <table class="table table-striped table-bordered" style="margin-bottom: 0">
        <tbody>
        {foreach $priceListRows as $priceListId => $priceListRow}
        <tr>
            <td width="50">{$priceListId + 1}</td>
            <td width="500">{$priceListRow.name}</td>
            <td width="500">{$priceListRow.providerRow.name}</td>
            <td width="100">{$priceListRow.currency}</td>
            <td width="200">{date("d.m.Y", strtotime($priceListRow.date))}</td>
            <td width="200">
                <input type="button" class="btn btn-outline-success" onClick="priceModal('priceListRow', 'id={$priceListRow.id}')" value="просмотр" data-toggle="modal" data-target="#modalDialog"/>
                <input type="button" class="btn btn-outline-secondary" onClick="exportExcel('{$priceListRow.id}')" value="экспорт"/>
            </td>
        </tr>
        {/foreach}
        </tbody>
        </table>
    </div>
</div>