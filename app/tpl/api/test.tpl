<form enctype="multipart/form-data" method="post">
    <p>
        <input type="file" name="f"/>
        <input type="submit" value="Отправить"/>
    </p>
</form>
{if isset($files)}
<ul>
{foreach from=$files key=key item=file}
    <li>
        <a target="_blank" href="/api/imagick/{$file.file}" download="{$file.name}">{$file.name}</a>
    </li>
{/foreach}
<ul>
{/if}