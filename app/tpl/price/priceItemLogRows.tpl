<h5>Поставшики</h5>
<div class="scroll-table">
    <table class="table table-striped table-bordered" style="margin-bottom: 0">
    <thead>
    <tr>
        <th scope="col" width="100" rowspan="2">Действие</th>
        <th scope="col" width="50"  rowspan="2">№</th>
        <th scope="col" width="100" rowspan="2">Время</th>
        <th scope="col" width="300" rowspan="2">Позиция (Артикул)</th>
        <th scope="col" width="200" colspan="2">Стоимость</th>
        <th scope="col" width="100" rowspan="2">Валюта</th>
        <th scope="col" width="200" rowspan="2">Прийс лист (название)</th>
        <th scope="col" width="200" rowspan="2">Поставщик</th>
    </tr>
    <tr>
        <th scope="col" width="100" rowspan="2">Старая</th>
        <th scope="col" width="100" rowspan="2">Новая</th>
    </tr>
    </thead>
    </table>
    <div class="scroll-table-body">
        <table class="table table-striped table-bordered" style="margin-bottom: 0">
        <tbody>
        {foreach $priceItemLogRows as $priceItemLogId => $priceItemLogRow}
        <tr>
            <td scope="col" width="100">
                {if !$priceItemLogRow.old_active}
                    Добавление
                {elseif !$priceItemLogRow.new_active}
                    Удаление
                {else}
                    Изменение
                {/if}
            </td>
            <td width="50">{$priceItemLogId + 1}</td>
            <td scope="col" width="100">{date("d.m.Y H:i", strtotime($priceItemLogRow.created))}</td>
            <td scope="col" width="300">{$priceItemLogRow.name_item} {$priceItemLogRow.article_item}</td>
            <td scope="col" width="100">{number_format($priceItemLogRow.old_price, 2, ".", "")}</td>
            <td scope="col" width="100">{number_format($priceItemLogRow.new_price, 2, ".", "")}</td>
            <td scope="col" width="100">{$priceItemLogRow.currency}</td>
            <td scope="col" width="200">{$priceItemLogRow.name_price_list}</td>
            <td scope="col" width="200">{$priceItemLogRow.name_provider}</td>
        </tr>
        {/foreach}
        </tbody>
        </table>
    </div>
</div>