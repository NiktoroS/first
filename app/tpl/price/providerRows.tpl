<h5>Поставшики</h5>
<div class="scroll-table">
    <table class="table table-striped table-bordered" style="margin-bottom: 0">
    <thead>
    <tr>
        <th scope="col" width="50">№</th>
        <th scope="col" width="500">Название</th>
        <th scope="col" width="100"></th>
    </tr>
    </thead>
    </table>
    <div class="scroll-table-body">
        <table class="table table-striped table-bordered" style="margin-bottom: 0">
        <tbody>
        {foreach $providerRows as $providerId => $rovaderRow}
        <tr>
            <td width="50">{$providerId + 1}</td>
            <td width="500">{$rovaderRow.name}</td>
            <td width="100"><input type="button" class="button" onClick="priceContent('priceListRows', 'id_provider={$rovaderRow.id}')" value="Прайс листы"/></td>
        </tr>
        {/foreach}
        </tbody>
        </table>
    </div>
</div>