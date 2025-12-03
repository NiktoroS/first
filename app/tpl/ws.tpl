<!DOCTYPE HTML>
<html>
<head>
  <title>water sort solver</title>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
</head>
<body>
<form action="/ws" enctype="multipart/form-data" method="POST">
  Уровень: <input type="text" name="level"     value="{$level}"     id="level"/><br/>
  Цветов:  <input type="text" name="cntColors" value="{$cntColors}" id="cntColors"/><br/>
  Файл:    <input type="file" name="file" id="file"/><br/>
  Код цвета: <input type="checkbox" onClick="showColors(this.checked)"/><br/>
  <input type="submit" id="submit"/>
  <table>
  <tr>
  {foreach $colors as $color}
    <td bgcolor="{$color}" class="color" onClick="getColor('{$color}')">
      <span class="span_colors">{$color}</span>
    </td>
  {/foreach}
  </tr>
  </table>
  <table>
  <tr>
  {for $bottle=0 to $cntColors + 1}
    {if in_array($bottle, $newLines)}
      <tr/><tr><td class="non-border" colspan="10">&nbsp;</td><tr/><tr>
    {/if}
    <td class="non-border">
      <table align=left>
        {for $col = 0 to 3}
        <tr>
          {$color = "#000000"}
          {if isset($bottles[$bottle][$col])}
            {$color = $colors[$bottles[$bottle][$col]]}
          {/if}
          <td bgcolor="{$color}" class="cell" id="td_bottle_{$bottle}_{$col}" onClick="setColor({$bottle}, {$col})">&nbsp;</td>
        </tr>
        {/for}
      </table>
    </td>
    <td class="non-border">&nbsp;&nbsp;</td>
  {/for}
  </tr>
  </table>
  Писать в БД:   <input type="checkbox" id="saveToDb"/><br/>
  Обратные ходы: <input type="checkbox" id="reverse"/><br/>
  Пересобрать:   <input type="checkbox" id="resolve"/><br/>
  <input type="button" value="Собрать" onClick="solve()"/>
  <table>
    <tr>
      <td>Start:</td>
      <td id="start"></td>
    </tr>
    <tr>
      <td>Finish:</td>
      <td id="finish"></td>
    </tr>
    <tr>
      <td>Cnt:</td>
      <td id="cntMoves"></td>
    </tr>
    <tr>
      <td>Delay:</td>
      <td id="delay"></td>
    </tr>
    <tr>
      <td>Moves:</td>
      <td id="moves"></td>
    </tr>
  </table>
  <input type="button" value="Сохранить" onClick="saveAcc()"/>
</form>
</body>
</html>

<script type="text/javascript" src="/js/jquery.min.js"></script>
<script type="text/javascript" src="/js/admin.js"></script>
<script type="text/javascript">
var color   = "";

function getColor(_color)
{
  color = _color;
  $("#color").val(color);
  $("#td_color").attr('bgcolor', color);
}

function setColor(bottle, col)
{
  $("#submit").attr('disabled', true);
  $("#td_bottle_" + bottle + "_" + col).attr('bgcolor', color);
}

function showColors(show)
{
  var spans = document.getElementsByClassName("span_colors");
  for (let i = 0; i < spans.length; i ++) {
    spans[i].style.display = show ? "block" : "none";
  }
}

function saveAcc()
{
  $.ajax({
    url: "/ajax",
    method: "POST",
    data: "module=ws&method=saveAcc&level=" + $("#level").val(),
    xhrFields: {
      responseType: "blob"
    },
    success: function(data) {
      var url = window.URL.createObjectURL(data);
      var a = document.createElement("a");
      a.style.display = "none";
      a.href = url;
      a.download = "ws_" + $("#level").val() + ".txt";
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
    },
    error: function(xhr, status, error) {
      console.error("Download failed:", error);
    }
  });
}

function solve()
{
  $("#submit").attr('disabled', false);
  var colors = document.getElementsByClassName("color");
  let bottles = [];
  for (let bottle = 0; bottle < parseInt($("#cntColors").val()) + 2; bottle ++) {
    let _bottle = [];
    for (let col = 0; col < 4; col ++) {
      var bgcolor = $("#td_bottle_" + bottle + "_" + col).attr('bgcolor');
      if (bgcolor) {
        for (let i = 0; i < colors.length; i++) {
          if ($(colors[i]).attr('bgcolor') == bgcolor) {
            _bottle.push(i);
          }
        }
      } else {
        _bottle.push(0);
      }
    }
    bottles.push(_bottle);
  }
  $("#start").html("");
  $("#finish").html("");
  $("#cntMoves").html("");
  $("#delay").html("");
  $("#moves").html("");

  let data = "bottles=" + JSON.stringify(bottles) + "&level=" + $("#level").val();
  if ($("#resolve").prop("checked")) {
    data += "&resolve=1";
  }
  if ($("#reverse").prop("checked")) {
    data += "&reverse=1";
  }
  if ($("#saveToDb").prop("checked")) {
    data += "&saveToDb=1";
  }
  let json = objAjaxJson(
    "ws",
    "solve",
    data
  );
  $("#submit").attr('disabled', false);
  $("#start").html(json.start);
  $("#finish").html(json.finish);
  if (!json.success) {
    if (json.colors) {
      for (let i = 0; i < colors.length; i ++) {
        let bgcolor = colors[i].attr('bgcolor');
        if (json.colors[bgcolor]) {
          colors[i].innerHtml(json.colors[bgcolor]);
        }
      }
    }
    return;
  }
  let moves = "";
  json.moves.forEach((move) => {
    moves += move.step + ": " + move.from + " -> " + move.to + "<br/>";
  })
  $("#cntMoves").html(json.cntMoves);
  $("#delay").html(json.delay);
  $("#moves").html(moves);
}
</script>
<style type="text/css">
body {
  background-color: black;
  color: #bbbbbb;
  font-family: arial;
}
table {
  border-collapse: collapse;
}
td {
  border: 1px solid white;
  padding: 0;
}
.color,
.cell {
  width: 25px;
  height: 25px;
}
.non-border { 
  border-style: none !important; 
  color: red;
}

.span_colors {
  display: none;
}
</style>
