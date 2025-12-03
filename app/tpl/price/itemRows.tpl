<h5>Позиции</h5>

<div class="scroll-table">
    <table class="table table-striped table-bordered" style="margin-bottom: 0">
    <thead>
    <tr>
        <th scope="col" width="50">№</th>
        <th scope="col" width="500">Название</th>
        <th scope="col" width="100">Артикул</th>
    </tr>
    </thead>
    </table>
    <div class="scroll-table-body">
        <table class="table table-striped table-bordered" style="margin-bottom: 0">
        <tbody>
        {foreach $itemRows as $itemId => $itemRow}
        <tr>
            <td width="50">{$itemId + 1}</td>
            <td width="500">{$itemRow.name}</td>
            <td width="100">{$itemRow.article}</td>
        </tr>
        {/foreach}
        </tbody>
        </table>
    </div>
</div>