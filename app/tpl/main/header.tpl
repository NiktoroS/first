<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
{foreach $metaRows as $name=>$content}
    <meta name="{$name}" content="{$content}"/>
{/foreach}

    <title>first</title>
    <link rel="stylesheet" href="/css/bootstrap/bootstrap.min.css"/>
    <link rel="stylesheet" href="/css/table_scrolling.css"/>
    <link rel="stylesheet" href="/css/jquetyui-themes/ui-lightness/jquery-ui.min.css"/>

    <script type="text/javascript" src="/js/jquery.min.js"></script>
    <script type="text/javascript" src="/js/bootstrap/bootstrap.min.js"></script>

    <script type="text/javascript" src="/js/table_scrolling.js"></script>
    <script type="text/javascript" src="/js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="/js/jquery.ui.datepicker-ru.min.js"></script>
    <script type="text/javascript" src="/js/admin.js?v=1"></script>
</head>
<body>