<?php
declare(strict_types=1);

/**
 * FastCore Front Controller (clean version)
 * - No output before session (prevents "headers already sent")
 * - Robust crash catching and forced logging to /logs
 * - DB init + BASE_URL helpers + secure session cookies
 * - Router/route includes
 */

// ===== [CRASH CATCHER & LOGGING] =========================================
error_reporting(E_ALL);
ini_set('display_errors', '1');               // set to '0' in production
ini_set('display_startup_errors', '1');       // set to '0' in production
ini_set('log_errors', '1');
ini_set('html_errors', '0');

// Avoid stale bytecode during debugging
@ini_set('opcache.enable', '0');
@ini_set('opcache.enable_cli', '0');

$ROOT = __DIR__;
$CORE = $ROOT . '/core';

$LOG_DIR = $ROOT . '/logs';
if (!is_dir($LOG_DIR)) { @mkdir($LOG_DIR, 0775, true); }
$ERROR_LOG = $LOG_DIR . '/php-error.log';
ini_set('error_log', $ERROR_LOG);

set_exception_handler(function (Throwable $e) {
    error_log('[UNCAUGHT] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    if (!headers_sent()) http_response_code(500);
    echo '<h1>Uncaught exception</h1><pre>',
        htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        '</pre>';
});

set_error_handler(function ($severity, $message, $file, $line) {
    // Promote all errors to exceptions (visibility + single flow)
    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $msg = "[FATAL] {$e['message']} in {$e['file']}:{$e['line']}";
        error_log($msg);
        if (!headers_sent()) http_response_code(500);
        echo '<h1>Fatal error</h1><pre>',
            htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '</pre>';
    }
});

// ===== [GUARD, LOCALE] ====================================================
if (!defined('FastCore')) define('FastCore', true);
@date_default_timezone_set('Asia/Tashkent');
@mb_internal_encoding('UTF-8');

// ===== [CORE BOOTSTRAP] ===================================================
// Use strict requires so missing files throw and get caught above
require $CORE . '/config.php';
require $CORE . '/func.php';
require $CORE . '/classes/db.php';
require $CORE . '/router.php';
require $ROOT . '/routes.php';

// Respect APP_DEBUG from config, but never disable logging
if (!defined('APP_DEBUG')) define('APP_DEBUG', false);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('display_startup_errors', APP_DEBUG ? '1' : '0');

// ===== [DB INIT] ==========================================================
try {
    $db = new db(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (method_exists($db, 'getPdo')) {
        $pdo = $db->getPdo();
        if ($pdo instanceof PDO) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }
    } elseif (method_exists($db, 'setShowErrors')) {
        $db->setShowErrors(APP_DEBUG);
    }
} catch (Throwable $e) {
    error_log('DB bootstrap error: ' . $e->getMessage());
    if (!headers_sent()) http_response_code(500);
    echo APP_DEBUG
        ? '<h1>Database bootstrap error</h1><pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>'
        : 'Internal server error.';
    exit;
}

// ===== [BASE_URL + func fallbacks] =======================================
if (!class_exists('func')) { class func {} }
if (!method_exists('func', 'isHttps')) {
    class func {
        public static function isHttps(): bool {
            return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443)
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        }
        public static function getHost(): string {
            return $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
        }
        public static function getBaseUrl(): string {
            $scheme = self::isHttps() ? 'https' : 'http';
            return $scheme . '://' . self::getHost();
        }
    }
} elseif (!method_exists('func', 'getBaseUrl')) {
    // add getBaseUrl if existing class lacks it
    class func extends func {
        public static function getBaseUrl(): string {
            $scheme = self::isHttps() ? 'https' : 'http';
            return $scheme . '://' . self::getHost();
        }
    }
}

if (!defined('BASE_URL')) {
    $scheme = func::isHttps() ? 'https' : 'http';
    $host   = func::getHost();
    if (strpos($host, ':') === false && !empty($_SERVER['SERVER_PORT'])) {
        $port = (int) $_SERVER['SERVER_PORT'];
        $isDefault = ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80);
        if (!$isDefault) $host .= ':' . $port;
    }
    define('BASE_URL', $scheme . '://' . $host);
}

// ===== [SESSION — BEFORE ANY OUTPUT] ======================================
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '', // set to your domain if needed
        'secure'   => func::isHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ===== [DISPATCH] =========================================================
try {
    if (function_exists('router_dispatch')) {
        router_dispatch(); // should render output
    } else {
        // Legacy behavior: routes.php must have included a page itself
        // If nothing renders, check routes.php / router.php mappings.
    }
} catch (Throwable $e) {
    error_log('Front controller exception: ' . $e->getMessage());
    if (!headers_sent()) http_response_code(500);
    echo APP_DEBUG
        ? '<h1>Application error</h1><pre>' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>'
        : 'Internal server error.';
    exit;
}
