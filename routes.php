<?php
declare(strict_types=1);

/**
 * File: routes.php
 * Description: Route definitions for FastCore (English, PHP 8+ compatible).
 * Fixes:
 *  - Defines admin prefix ($adm) to prevent "Undefined variable $adm".
 *  - Provides safe defaults for DB_* constants if not defined yet.
 *  - Translated comments into English.
 */

if (!defined('FastCore')) {
    define('FastCore', true);
}

/* ===== Admin Prefix ===== */
if (!defined('ADMIN_PREFIX')) {
    define('ADMIN_PREFIX', 'admin'); // change this if your admin path differs
}
$admin = ADMIN_PREFIX; // legacy compatibility

/* ===== DB constants safeguard ===== */
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'fastcore');

/* ===== Route definitions ===== */
$GLOBALS['routes'] = array(

    // Error & Main
    '_404'             => '../inc/404.php',     // 404 Page
    '/'                => 'home.php',           // Home
    '/i/([0-9]+)?'     => '/home.php',          // Referral link
    '/stats'           => 'stats.php',          // Statistics
    '/login'           => 'login.php',          // Login
    '/register'             => 'reg.php',            // Registration
    '/restore'         => 'restore.php',        // Password recovery
    '/news'            => 'news.php',           // News
    '/news/p/([0-9]+)?'=> 'news.php',           
    '/reviews'         => 'reviews.php',        // Reviews
    '/reviews/add'     => 'reviews.php',
    '/reviews/p/([0-9]+)?'=> 'reviews.php',
    '/about'           => 'about.php',          // About project
    '/terms'           => 'terms.php',          // Terms & rules
    '/help'            => 'help.php',           // Support

    // User account
    '/user/dashboard'  => 'dashboard.php',      // Profile
    '/user/bonus'      => 'bonus.php',          // Bonuses
    '/user/shop'       => 'shop.php',           // Shop characters
    '/user/store'      => 'store.php',          // Profit collection
    '/user/insert'     => 'insert.php',         // Deposit
    '/user/insert/payeer'    => 'insert.php',
    '/user/insert/freekassa' => 'insert.php',
    '/user/pay'        => 'pay.php',            // Withdraw method
    '/user/pay/([^/]+)'=> 'pay.php',            // Withdraw specific
    '/user/exchange'   => 'exchange.php',       // Exchange
    '/user/refs'       => 'referals.php',       // Referrals
    '/user/settings'   => 'settings.php',       // Settings
    '/user/logout'     => 'dashboard.php',      // Logout

    // Admin panel
    '/'.$admin           => 'login.php',          // Admin login
    '/'.$admin           => 'main.php',           // Admin main
    '/'.$admin.'/'       => 'main.php',           
    '/'.$admin.'/config' => 'config.php',         // Settings
    '/'.$admin.'/users'  => 'users.php',          // Users
    '/'.$admin.'/users/info/([0-9]+)?' => 'users.php',
    '/'.$admin.'/users/p/([0-9]+)?'   => 'users.php',
    '/'.$admin.'/st/([^/]+)'          => 'stats.php', // Stats
    '/'.$admin.'/news'   => 'news.php',           // News
    '/'.$admin.'/news/add' => 'news.php',
    '/'.$admin.'/news/edit/([0-9]+)?' => 'news.php',
    '/'.$admin.'/pers'   => 'pers.php',           // Characters
    '/'.$admin.'/pers/add' => 'pers.php',
    '/'.$admin.'/pers/edit/([0-9]+)?' => 'pers.php',
);
