<?php if (!defined('FastCore')) {
    exit();
} ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <title><?= $config->sitename ?> - {!TITLE!} </title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="{!DESCRIPTION!}">
    <meta name="keywords" content="{!KEYWORDS!}">
    <link rel="shortcut icon" href="/img/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-xl">
        <a class="navbar-brand" href="/">
            <b>.:FAST<span class="text-warning">CORE:.</span></b>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExampleDefault"
                aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarsExampleDefault">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="/news">Новости</a></li>
                <li class="nav-item"><a class="nav-link" href="/about">О проекте</a></li>
                <li class="nav-item"><a class="nav-link" href="/stats">Статистика</a></li>
                <li class="nav-item"><a class="nav-link" href="/reviews">Отзывы</a></li>
                <li class="nav-item"><a class="nav-link" href="/terms">Правила</a></li>
                <li class="nav-item"><a class="nav-link" href="/help">Помощь</a></li>
            </ul>
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if ($uid) : ?>
                    <li class="nav-item"><a class="nav-link" href="/user/dashboard"><i class="fa fa-user"></i> Профиль</a></li>
                    <li class="nav-item"><a class="nav-link" href="/user/logout"><i class="fa fa-sign-out"></i> Выйти</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/reg"><b>Регистрация</b></a></li>
                    <li class="nav-item"><a class="nav-link" href="/login"><b>Вход</b></a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container-xl mt-2">
<?php if (isset($pg->segment[0]) && empty($pg->segment[0] === '') && empty($pg->segment[0] === 'i') && empty($pg->segment[0] === 'user')) : ?>
    <div class="text-center">
        <h3 class="text-uppercase title"><b>{!TITLE!}</b></h3>
    </div>
    <div class="wrapper">
<?php else: ?>
    <div class="mt-3"></div>
<?php endif; ?>
