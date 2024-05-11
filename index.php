<?php

define('Environment', 'development'); // development, production

if (Environment === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 0);
}

/***
 * Engine - FastCore v0.9 08.08.2020
 * The script is intended for free use.
 * Development and support: Jumast & Kolyaka105
 * Contacts: jumast@ya.ru - kolya105@ukr.net
 * Updates here: https://vk.com/fastcore
 * Improved Version Repository: https://github.com/rubensrocha/fastcore
 **/

# Generate page
define('GenTime', microtime(true));

# Start session
session_start();

# Start buffer
ob_start();

# Default title
$opt = [];

# Constants for Include
define('FastCore', true);
define('FastCoreVersion', '0.8.1');

# System
spl_autoload_register(static function ($className) {
    require 'core/' . $className . '.php';
});

require_once 'core/config.php'; // Include the file that connects to the database


# Config class
$config = new config();


# Functions
$func = new func();
$func->getDomain();

# Admin directory
$adm = $config->adm_dir;

# Find routes
require 'routes.php';

# Include router
$pg = new router();
$routedFile = $pg->classname;

# User
$uid = $_SESSION['uid'] ?? '0';
$login = $_SESSION['login'] ?? 'Guest';

# Outputting pages
if (isset($pg->segment[0]) && !empty($pg->segment[0] === 'user')) {

    # Authorized or not
    if (isset($_SESSION['uid']) > 0) {

        # If authorized, search in the database
        $user = $db->query('SELECT * FROM db_users WHERE id = ?', [$uid])->fetchArray();

        require 'inc/head.php';

        require 'inc/menu.php'; // Account menu
        echo '<div class="content"><div class="wrapper">';
        require 'inc/title.php'; // Title
        require 'pages/user/' . $routedFile; // Account pages
        echo '</div></div><div class="clearfix"></div>'; // content div

        require 'inc/foot.php';
    } else {
        header('Location: /');
        return;
    }
} # Admin panel
elseif (isset($pg->segment[0]) && !empty($pg->segment[0] === $adm) ?? $pg->segment[0] === $adm) {
    if (isset($_SESSION["admin"])) {
        require 'pages/' . $adm . '/inc/head.php';
        require 'pages/' . $adm . '/inc/menu.php';
        require 'pages/' . $adm . '/' . $routedFile;
        require 'pages/' . $adm . '/inc/foot.php';
    } else {
        require 'pages/' . $adm . '/inc/head.php';
        require 'pages/' . $adm . '/login.php'; // Admin login
    }
}

# Surfing IFRAME
/*
elseif (!empty($pg->segment[0] === 'link')) {
    if(isset($_SESSION["uid"])){
        require 'inc/view.php';
    }
}
*/

# Guest
else {
    require 'inc/head.php';
    require 'pages/' . $routedFile;
    require 'inc/foot.php';
}

# Page generation end
$genPage = round((microtime(true) - GenTime), 5);

# Replace data
if ($pg->found) {
    if (isset($pg->segment[0]) && empty($pg->segment[0] === $adm)) {  // off-admin
        $content = str_replace('{!TITLE!}', $opt['title'], $content);
    }
    if (isset($pg->segment[0]) && empty($pg->segment[0] === 'user') && empty($pg->segment[0] === $adm)) { // off-account
        $content = str_replace(array('{!DESCRIPTION!}', '{!KEYWORDS!}'), array($opt['description'], $opt['keywords']), $content);
    }
} else {
    $content = str_replace(array('{!TITLE!}', '{!DESCRIPTION!}', '{!KEYWORDS!}'), array('Page not found', '', ''), $content);
}

$content = str_replace('{!GEN_PAGE!}', sprintf("%.5f", ($genPage)), $content);

# Output content
echo $content;
