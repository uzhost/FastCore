<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

/***
 * Движок - FastCore v0.8 08.08.2020
 * Скрипт предназначен для свободного пользования.
 * Разработка и поддержка: Jumast & Kolyaka105 
 * Контакты: jumast@ya.ru - kolya105@ukr.net
 * Обновления тут:  https://vk.com/fastcore
 **/

# Генерация страницы
define('GenTime', microtime(true));

# Старт сессии
session_start();

# Старт буфера
ob_start();

# Default title
$opt = array();

# Константа для Include
define('FastCore',true);

# Система
spl_autoload_register(function ($lfc) {
    require 'core/' . $lfc . '.php';
});

# Класс конфига
$config = new config;

# Функции
$func = new func;
$func->getDomain();

# Директория админки
$adm = $config->adm_dir;

# Ищем роуты
require('routes.php');

# Подключаем роутер
$pg = new router();
$routed_file = $pg->classname;

# Пользователь
$uid = $_SESSION['uid'] ?? '0';
$login = $_SESSION['login'] ?? 'Guest';

# ==========================
#  Начало вывода страниц
# ==========================

# Аккаунт
if (!empty($pg->segment[0] === 'user')) {

	# Авторизованный или не
	if(isset($_SESSION['uid']) > 0) {

		# Если авторизованный ищем в БД
		$user = $db->query('SELECT * FROM db_users WHERE id = ?',$uid)->fetchArray();
	
		require('inc/head.php');

	require('inc/menu.php'); // Меню аккаунта
	echo '<div class="content"><div class="wrapper">';
	require('inc/title.php'); // Заголовок
	require('pages/user/'.$routed_file); // Страницы аккаунта
	echo '</div></div><div class="clearfix"></div>'; // див контент

		require('inc/foot.php');
	}
	else { header('Location: /'); return; }
}


# Админка
elseif (!empty($pg->segment[0] === ''.$adm.'') ?? $pg->segment[0] === ''.$adm.'') {
	if(isset($_SESSION["admin"])){
		require('pages/'.$adm.'/inc/head.php');
		require('pages/'.$adm.'/inc/menu.php');
		require('pages/'.$adm.'/'.$routed_file);
		require('pages/'.$adm.'/inc/foot.php');
	}
	else {
		require('pages/'.$adm.'/inc/head.php');
		require('pages/'.$adm.'/login.php'); // Вход в админку
	}
}

# Серфинг IFRAME
/*
elseif (!empty($pg->segment[0] === 'link')) {
	if(isset($_SESSION["uid"])){
		require('inc/view.php');
	}
}
*/

# Гостевая
else {
	require('inc/head.php');
	require('pages/'.$routed_file);
	require('inc/foot.php');
}

# ==========================
# Конец вывода страниц
# ==========================


# Заносим контент в переменную
$content = ob_get_contents();

# Очищаем буфер
ob_end_clean();

# Генерация страницы конец
$gen_page = round((microtime(true) - GenTime), 5);

# Заменяем данные
if (empty($pg->segment[0] === ''.$adm.'')) {  // off-admin
$content = str_replace('{!TITLE!}',$opt['title'],$content); 
}
if (empty($pg->segment[0] === 'user') && empty($pg->segment[0] === ''.$adm.'')) { // off-account
$content = str_replace('{!DESCRIPTION!}',$opt['description'],$content);
$content = str_replace('{!KEYWORDS!}',$opt['keywords'],$content); 
}
$content = str_replace('{!GEN_PAGE!}', sprintf("%.5f", ($gen_page)) ,$content);

# Выводим контент
echo $content;
?>