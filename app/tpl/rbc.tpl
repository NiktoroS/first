{include file="main/header.tpl"}

{*
<form class="form-inline">
    <div class="form-group">
        <label for="time">Время:</label>
        <input type="text" name="time" value="{date("Y-m-d H:i:00")}"/>
    </div>
    <div class="form-group">
        <label for="value">Бабло:</label>
        <input type="text" name="value" value=""/>
    </div>
    <button type="submit" class="btn btn-default">Сохранить</button>
</form>
*}

{$types = array ("cur", "last", "all")}

{foreach $types as $type}
    <div id="curve_chart_{$type}" style="width: 1500px; height: 1000px"></div>
{/foreach}

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<script type="text/javascript">
google.charts.load('current', { 'packages':['corechart'] });

{foreach $types as $type}

google.charts.setOnLoadCallback(drawChart_{$type});

function drawChart_{$type} () {
    var data = google.visualization.arrayToDataTable([
        ['Дата', 'Brand', '{${$type}.divide} / USD', 'USD'],
    {foreach ${$type}.rows as $row}
        ['{$row.date}', {$row.brand}, {${$type}.divide / $row.USD}, {$row.USD}],
    {/foreach}
    ]);

    var options = {
        title: '{$type}',
        curveType: 'function',
        legend: { position: 'bottom' },
        backgroundColor: 'black',
        titleTextStyle: { color: 'gray' },
        vAxis: { minValue: {${$type}.min} },
    };

    var chart = new google.visualization.LineChart(document.getElementById('curve_chart_{$type}'));
    chart.draw(data, options);
}

{/foreach}

</script>

{include file="main/footer.tpl"}