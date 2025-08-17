<?php
declare(strict_types=1);

/**
 * File: index.php
 * Description: Front controller (bootstrap) for FastCore. English-translated, PHP 8+ compatible.
 * Responsibilities:
 *  - Define the 'FastCore' constant guard
 *  - Bootstrap configuration, autoload classes, and helpers
 *  - Initialize error handling and timezone
 *  - Dispatch the request via the router
 */

// ===== Guard ==============================================================
if (!defined('FastCore')) {
    define('FastCore', true);
}

// ===== Environment / paths ===============================================
$ROOT = __DIR__;
$CORE = $ROOT . '/core';
$INC  = $ROOT . '/inc';

// ===== Error handling (dev/prod) =========================================
// Toggle this based on your environment. In production, disable display_errors.
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
// Use a writable path (adjust if needed)
if (!ini_get('error_log') || ini_get('error_log') === '') {
    ini_set('error_log', $ROOT . '/logs/php-error.log');
}

// ===== Timezone & encoding ===============================================
date_default_timezone_set('Asia/Tashkent');
mb_internal_encoding('UTF-8');

// ===== Autoload / core requires ==========================================
require_once $CORE . '/config.php';
require_once $CORE . '/func.php';        // provides class func (with getDomain(), etc.)
require_once $CORE . '/classes/db.php';  // PDO-based db wrapper
require_once $CORE . '/router.php';      // router include
require_once $ROOT . '/routes.php';      // route definitions

// ===== Initialize global services ========================================
// Database instance
try {
    $db = new db(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    // Optional: uncomment for dev
    // $db->setShowErrors(APP_DEBUG);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Database bootstrap error.');
}

// Optional globals (if existing code expects them)
$GLOBALS['db'] = $db;

// ===== Basic security headers ============================================
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ===== Determine base URL (if needed globally) ===========================
if (!defined('BASE_URL')) {
    $scheme = func::isHttps() ? 'https' : 'http';
    $host = func::getHost();
    // Include non-default port if necessary
    if (strpos($host, ':') === false && !empty($_SERVER['SERVER_PORT'])) {
        $port = (int)$_SERVER['SERVER_PORT'];
        $isDefault = ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80);
        if (!$isDefault) {
            $host .= ':' . $port;
        }
    }
    define('BASE_URL', $scheme . '://' . $host);
}

// ===== Start session ======================================================
if (session_status() !== PHP_SESSION_ACTIVE) {
    // Secure defaults; tweak for your domain
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '', // set to your domain if needed
        'secure' => func::isHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ===== Dispatch request via router =======================================
try {
    // Router should capture current URI and include the matched page/controller
    // core/router.php + routes.php are responsible for emitting the content.
    // If your router populates variables (like $page), this is compatible.
    // Example (if router exposes a dispatch() function):
    if (function_exists('router_dispatch')) {
        router_dispatch();
    } else {
        // Legacy: many FastCore builds just include pages via routes.php rules
        // In that case, router.php may already have performed the include.
        // Nothing to do here.
    }
} catch (Throwable $e) {
    if (APP_DEBUG) {
        http_response_code(500);
        echo '<h1>Application error</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
    } else {
        http_response_code(500);
        echo 'Internal server error.';
    }
    exit;
}
