{include file="main/header.tpl"}
<table border="1">
{for $r1 = 0; $r1 < 3; $r1++}
<tr>
    {for $c1 = 0; $c1 < 3; $c1++}
    <td>
        <table border="1" class="game">
        {for $r2 = 0; $r2 < 3; $r2++}
        <tr>
            {for $c2 = 0; $c2 < 3; $c2++}
            <td id="game_{$r1*3+$r2}_{$c1*3+$c2}" width="25" height="25" align="center" onClick="putNumber(this)"></td>
            {/for}
        </tr>
        {/for}
        </table>
        </td>
    {/for}
</tr>
{/for}
</table>

<table border="1">
<tbody class="set">
{for $r = 0; $r < 3; $r++}
<tr>
    {for $c = 0; $c < 3; $c++}
        <td id="set_{$r * 3 + $c + 1}" width="25" height="25" align="center" class="noactive" onClick="setNumber(this)">{$r * 3 + $c + 1}</td>
    {/for}
</tr>
{/for}
</tbody>
</table>
<input type="button" value="E"     onClick="setEmpty()"/>
<input type="button" value="Clear" onClick="clearAll()"/>
<input type="button" value="Solve" onClick="solveAll()"/>

<p id="time"/>

<script type="text/javascript" src="/js/sud.js?v=1"></script>
{include file="main/footer.tpl"}