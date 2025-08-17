<?php
declare(strict_types=1);

if (!defined('FastCore')) {
    exit('Oops!');
}

// ====================================
// Global Debugger & Error Handling
// ====================================

// Toggle debugging here (true = verbose, false = silent prod)
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', true);
}

// Report all errors
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('display_startup_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

// Ensure log dir exists (relative to this file)
$__logDir = __DIR__ . '/../logs';
if (!is_dir($__logDir)) {
    @mkdir($__logDir, 0755, true);
}
$__logFile = $__logDir . '/php-error.log';
ini_set('error_log', $__logFile);

// Simple HTML escaper local to this file ONLY (avoid dependency on func.php)
$__esc = function (string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

// Error handler: log always; echo only when APP_DEBUG is true
set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($__esc) {
    $msg = "PHP ERROR [$errno] $errstr in $errfile:$errline";
    error_log($msg);
    if (APP_DEBUG) {
        echo "<pre style='color:#900;background:#fee;border:1px solid #d88;padding:8px'>"
           . $GLOBALS['__esc']($msg) . "</pre>";
    }
    return false; // continue to PHP's internal handler too
});

// Fatal catcher
register_shutdown_function(function () use ($__esc) {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $msg = "FATAL: {$e['message']} in {$e['file']}:{$e['line']}";
        error_log($msg);
        if (APP_DEBUG) {
            echo "<pre style='color:#fff;background:#c00;padding:8px'>"
               . $GLOBALS['__esc']($msg) . "</pre>";
        }
    }
});

// Exception handler
set_exception_handler(function (Throwable $ex) use ($__esc) {
    $msg = "UNCAUGHT " . get_class($ex) . ": " . $ex->getMessage()
         . " in " . $ex->getFile() . ":" . $ex->getLine()
         . "\n" . $ex->getTraceAsString();
    error_log($msg);
    if (APP_DEBUG) {
        echo "<pre style='color:#004;background:#eef;padding:8px;white-space:pre-wrap'>"
           . $GLOBALS['__esc']($msg) . "</pre>";
    } else {
        echo "Internal Server Error";
    }
});



/**
 * File: core/config.php
 * Description: Application configuration (no DB connections here). Defines environment,
 *              database, security, admin, and payment gateway settings.
 *
 * SECURITY:
 * - Prefer environment variables for secrets. Do NOT commit real creds to Git.
 * - Keep APP_DEBUG false in production.
 */

if (!defined('FastCore')) { define('FastCore', true); }

// core/config.php
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', true);
}

/* =========================
   Environment & Debug
   ========================= */
if (!defined('APP_ENV'))   define('APP_ENV', getenv('APP_ENV') ?: 'production'); // production|development
if (!defined('APP_DEBUG')) define('APP_DEBUG', APP_ENV === 'development');

if (!defined('APP_TZ'))    define('APP_TZ', getenv('APP_TZ') ?: 'Asia/Tashkent');
date_default_timezone_set(APP_TZ);

/* =========================
   Database (constants only)
   ========================= */
// Prefer env; fallback to safe placeholders.
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1:3311');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'fastcore');         // CHANGE ME
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: 'yourdbpassword');    // CHANGE ME
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'fastcore');     // CHANGE ME

// Optional socket path if you use unix_socket auth (leave undefined to use TCP)
// if (!defined('DB_SOCKET')) define('DB_SOCKET', getenv('DB_SOCKET') ?: '/var/lib/mysql/mysql.sock');

/* =========================
   Application URLs & Paths
   ========================= */
if (!defined('APP_URL'))  define('APP_URL', getenv('APP_URL') ?: ''); // leave empty to auto-detect in index.php
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__));       // project root (../ from /core)

/* =========================
   Security / Sessions / CSRF
   ========================= */
if (!defined('SESSION_NAME'))     define('SESSION_NAME', getenv('SESSION_NAME') ?: 'fastcore_sid');
if (!defined('SESSION_SAMESITE')) define('SESSION_SAMESITE', getenv('SESSION_SAMESITE') ?: 'Lax'); // Lax|Strict
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', (int)(getenv('SESSION_LIFETIME') ?: 0)); // 0 = session cookie
if (!defined('CSRF_TTL'))         define('CSRF_TTL', (int)(getenv('CSRF_TTL') ?: 7200)); // 2 hours

// App key (for HMAC/tokens). Use a long random value; store in env in production.
if (!defined('APP_KEY')) define('APP_KEY', getenv('APP_KEY') ?: 'CHANGE_THIS_APP_KEY_32+CHARS');

// Password hashing cost (bcrypt)
if (!defined('PASSWORD_COST')) define('PASSWORD_COST', (int)(getenv('PASSWORD_COST') ?: 12));

/* =========================
   Admin
   ========================= */
// URL prefix for admin routes (maps to pages/admin/* files)
if (!defined('ADMIN_PREFIX')) define('ADMIN_PREFIX', getenv('ADMIN_PREFIX') ?: 'admin');

// Admin access (example: basic panel auth). Prefer storing HASH, not plain.
if (!defined('ADMIN_USER')) define('ADMIN_USER', getenv('ADMIN_USER') ?: 'admin');
// For better security, store a password hash and compare with password_verify()
// Example: generate with password_hash('YourStrongAdminPass', PASSWORD_BCRYPT)
if (!defined('ADMIN_PASS_HASH')) define('ADMIN_PASS_HASH', getenv('ADMIN_PASS_HASH') ?: ''); // leave '' if unused

/* =========================
   Mail (optional SMTP)
   ========================= */
if (!defined('MAIL_FROM'))  define('MAIL_FROM', getenv('MAIL_FROM') ?: 'no-reply@example.com');
if (!defined('MAIL_HOST'))  define('MAIL_HOST', getenv('MAIL_HOST') ?: '');
if (!defined('MAIL_USER'))  define('MAIL_USER', getenv('MAIL_USER') ?: '');
if (!defined('MAIL_PASS'))  define('MAIL_PASS', getenv('MAIL_PASS') ?: '');
if (!defined('MAIL_PORT'))  define('MAIL_PORT', getenv('MAIL_PORT') ?: '');
if (!defined('MAIL_SECURE'))define('MAIL_SECURE', getenv('MAIL_SECURE') ?: 'tls'); // tls|ssl

/* =========================
   Payments — Payeer
   =========================
   Typical usage:
   - Merchant account for accepting payments.
   - API credentials for payouts/queries.
*/
if (!defined('PAYEER_ENABLED'))     define('PAYEER_ENABLED', (bool)(getenv('PAYEER_ENABLED') ?: false));
if (!defined('PAYEER_MERCHANT_ID')) define('PAYEER_MERCHANT_ID', getenv('PAYEER_MERCHANT_ID') ?: '');
if (!defined('PAYEER_MERCHANT_KEY'))define('PAYEER_MERCHANT_KEY', getenv('PAYEER_MERCHANT_KEY') ?: ''); // secret key for merchant sign
// Optional API creds (for payouts, balance, etc.)
if (!defined('PAYEER_ACCOUNT'))     define('PAYEER_ACCOUNT', getenv('PAYEER_ACCOUNT') ?: '');    // e.g., P1234567
if (!defined('PAYEER_API_ID'))      define('PAYEER_API_ID', getenv('PAYEER_API_ID') ?: '');
if (!defined('PAYEER_API_KEY'))     define('PAYEER_API_KEY', getenv('PAYEER_API_KEY') ?: '');

/* =========================
   Payments — FreeKassa
   =========================
   Typical usage:
   - FK_MERCHANT_ID (shop ID), secret keys 1 & 2 for validation/callbacks.
*/
if (!defined('FK_ENABLED'))       define('FK_ENABLED', (bool)(getenv('FK_ENABLED') ?: false));
if (!defined('FK_MERCHANT_ID'))   define('FK_MERCHANT_ID', getenv('FK_MERCHANT_ID') ?: '');
if (!defined('FK_SECRET_1'))      define('FK_SECRET_1', getenv('FK_SECRET_1') ?: '');
if (!defined('FK_SECRET_2'))      define('FK_SECRET_2', getenv('FK_SECRET_2') ?: '');
if (!defined('FK_CURRENCY'))      define('FK_CURRENCY', getenv('FK_CURRENCY') ?: 'RUB');
if (!defined('FK_TEST_MODE'))     define('FK_TEST_MODE', (bool)(getenv('FK_TEST_MODE') ?: false));
// Optional: IP whitelist for callbacks, comma-separated
if (!defined('FK_IP_WHITELIST'))  define('FK_IP_WHITELIST', getenv('FK_IP_WHITELIST') ?: '');

/* =========================
   Error display / Logging
   ========================= */
ini_set('display_errors', APP_DEBUG ? '1' : '1');
ini_set('display_startup_errors', APP_DEBUG ? '1' : '1');
ini_set('log_errors', '1');

// If no error_log configured, write under project /logs (ensure writable)
if (!ini_get('error_log') || ini_get('error_log') === '') {
    $logDir = APP_ROOT . '/logs';
    if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
    ini_set('error_log', $logDir . '/php-error.log');
}
