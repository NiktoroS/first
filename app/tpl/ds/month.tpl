{foreach $statRows as $row}
<li>
    {$row["day"]}/{$params["month"]}/{$params["year"]}: {$row["delta"]}
</li>
{/foreach}