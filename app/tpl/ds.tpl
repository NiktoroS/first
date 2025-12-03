<!DOCTYPE HTML>
<html>
<head>
    <title>deep state</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
</head>
<body>
<table>
<tr>
    <th>Тип<input type="checkbox" id="attr_name_check"/></th>
    <th>Всего</th>
    {foreach range(2022, intval(date("Y"))) as $year}
        <th>{$year}</th>
    {/foreach}
</tr>
{foreach $attrs as $attr}
<tr valign="top">
    <th>
        <span name="attr_name" style="display: none;">{convert_uudecode($attr["name"])}</span>
    </th>
    <td align="right">
        {$attr["stat"]["total"]}
    </td>
    {foreach range(2022, intval(date("Y"))) as $year}
        <td align="right">
           {if isset($attr["stat"]["detail"][$year])}
               <span onClick="year({$attr["id"]}, '{$year}')" class="text-right">
                   {$attr["stat"]["detail"][$year]}
               </span>
           {/if}
           <ul id="{$attr["id"]}_{$year}"></ul>
        </td>
    {/foreach}
</tr>
{/foreach}
</table>
<form action="/ds/save" enctype="multipart/form-data" method="POST">
    Файл: <input type="file" name="file" id="file"/><br/>
    <input type="submit" value="Сохранить"/>
</form>
</body>
</html>

<script type="text/javascript" src="/js/jquery.min.js"></script>
<script type="text/javascript" src="/js/admin.js"></script>
<script type="text/javascript">

$("#attr_name_check").on("click", function() {
  console.log("checked", $("#attr_name_check").prop("checked"));
  $("span[name='attr_name']").each(function(index, element) {
    if ($("#attr_name_check").prop("checked")) {
      $(element).show();
    } else {
      $(element).hide();
    }
  });
});

function attrName()
{
   $("name").each()
}

function month(attr_id, year, month)
{
    let id = attr_id + "_" + year + "_" + month;
    let ul = $("#" + id);
    if (ul.html()) {
        ul.html("");
        ul.hide();
        return;
    }
    objAjax(
        "ds",
        "month",
        "attr_id=" + attr_id + "&year=" + year + "&month=" + month,
        id
    );
    ul.show();
}

function year(attr_id, year)
{
    let id = attr_id + "_" + year;
    let ul = $("#" + id);
    if (ul.html()) {
        ul.html("");
        ul.hide();
        return;
    }
    objAjax(
        "ds",
        "year",
        "attr_id=" + attr_id + "&year=" + year,
        id
    );
    ul.show();
}

</script>