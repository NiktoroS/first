{foreach $statRows as $row}
<li>
    <span onClick="month({$params["attr_id"]}, '{$params["year"]}', '{$row["month"]}')">
        {$row["month"]}/{$params["year"]}: {$row["delta"]}
    </span>
    <ul id="{$params["attr_id"]}_{$params["year"]}_{$row["month"]}"></ul>
</li>
{/foreach}