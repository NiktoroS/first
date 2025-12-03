{include file="main/header.tpl"}

<form class="form-inline">
    <div class="form-group">
        <label for="time">Время:</label>
        <input type="text" name="time" value="{date("Y-m-d H:i")}"/>
    </div>
    <div class="form-group">
        <label for="value">Бабло:</label>
        <input type="tel" name="value" value=""/>
    </div>
    <button type="submit" class="btn btn-default">Сохранить</button>
</form>

<div id="curve_chart" style="height: 800px"></div>

<script type="text/javascript" src="/js/loader.js"></script>

<script type="text/javascript">
google.charts.load('current', { 'packages':['corechart'] });
google.charts.setOnLoadCallback(drawChart);

function drawChart() {
    var data = google.visualization.arrayToDataTable([
        ['Время', 'Сумма', 'Динамика'],
    {foreach $rows as $row}
        ['{$row.time}', {$row.value}, {$row.delta}],
    {/foreach}
    ]);

    var options = {
        title: 'Расход электричества',
        curveType: 'function',
        legend: { position: 'bottom' },
        backgroundColor: 'black',
        titleTextStyle: { color: 'gray' },
//        hAxis: { titleTextStyle: { color: 'gray' } },
        vAxis: { minValue: 0 },
    };

    var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));

    chart.draw(data, options);
}
</script>

{include file="main/footer.tpl"}