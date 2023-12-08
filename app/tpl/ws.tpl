<!DOCTYPE HTML>
<html>
<head>
<title>water sort solver</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">

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
.color {
  width: 25px;
  height: 25px;
}
.non-border { 
  border-style: none !important; 
  color: red;
}
}

</style>

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

function setColor(bootle, col)
{
  $("#submit").attr('disabled', true);
  if (color == $("#td_bootle_" + bootle + "_" + col).attr('bgcolor')) {
    $("#td_bootle_" + bootle + "_" + col).attr('bgcolor', "black");
    return;
  }
  $("#td_bootle_" + bootle + "_" + col).attr('bgcolor', color);
}

function solve(bootle, col)
{
  $("#submit").attr('disabled', false);
  let bootles = [];
  for (let bootle = 0; bootle < $("#bootles").val(); bootle ++) {
    let _bootle = [];
    for (let col = 0; col < 4; col ++) {
      _bootle.push($("#td_bootle_" + bootle + "_" + col).attr('bgcolor'));
    }
    bootles.push(_bootle);
  }
  bootles.push([null, null, null, null]);
  bootles.push([null, null, null, null]);
  let json = objAjaxJson("ws", "solve", "level=" + $("#level").val() + "&bootles=" + JSON.stringify(bootles));
  $("#start").html(json.start);
  $("#finish").html(json.finish);
  if (!json.success) {
    if (json.colors) {
      $("td").each(function() {
        if ("td_colors" != this.id) {
          return
        }
        let bgcolor = $(this).attr('bgcolor');
        if (json.colors[bgcolor]) {
          $(this).html(json.colors[bgcolor]);
        }
      })
    }
    return;
  }
  let moves = "";
  console.log(json.moves)
  json.moves.forEach((move) => {
    moves += move.step + ": " + move.from + " -> " + move.to + "<br/>";
  })
  $("#moves").html(moves);
}

</script>

</head>
<body>
<form action="/ws" enctype="multipart/form-data" method="POST">
  Уровень:<input type="text" name="level" value="{$level}" id="level" /><br/>
  Бутылок:<input type="text" name="bootles" value="{$bootles}" id="bootles" /><br/>
  Файл:<input type="file" name="file" id="file"/><br/>
  <input type="submit" id="submit"/>
  <table>
  <tr>
    <td class="color" id="td_color">&nbsp;</td>
    <td class="non-border">&nbsp;&nbsp;</td>
    {foreach $colors as $color}
      <td bgcolor="{$color}" class="color" id="td_colors" onClick="getColor('{$color}')">&nbsp</td>
    {/foreach}
    <td class="color" onClick="getColor(null)">&nbsp</td>
  </tr>
  </table>
  <table>
  <tr>
  {for $bootle=0 to $bootles - 1}
    {if in_array($bootle, $newLines)}
      <tr/><tr><td class="non-border" colspan="10">&nbsp;</td><tr/><tr>
    {/if}
    <td class="non-border">
      <table align=left>
        {for $col = 0 to 3}
        <tr>
          <td class="color" id="td_bootle_{$bootle}_{$col}" onClick="setColor({$bootle}, {$col})">&nbsp;</td>
        </tr>
        {/for}
      </table>
    </td>
    <td class="non-border">&nbsp;&nbsp;</td>
  {/for}
  </tr>
  </table>
  <input type="button" value="Собрать" onClick="solve()" />
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
      <td>Moves:</td>
      <td id="moves"></td>
    </tr>
  </table>
</form>
</body>
</html>