<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
{foreach $metaRows as $name=>$content}
    <meta name="{$name}" content="{$content}"/>
{/foreach}

    <title>first</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="/css/table_scrolling.css"/>
    <link rel="stylesheet" href="/css/jquety-ui/jquery-ui.min.css"/>

    <script type="text/javascript" src="/js/jquery.min.js"></script>
    <script type="text/javascript" src="/js/bootstrap.min.js"></script>

    <script type="text/javascript" src="/js/table_scrolling.js?v=1"></script>
    <script type="text/javascript" src="/js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="/js/jquery.ui.datepicker-ru.min.js"></script>
    <script type="text/javascript" src="/js/main.js?v=1"></script>
</head>
<body>