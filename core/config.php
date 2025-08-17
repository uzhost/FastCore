<?php
declare(strict_types=1);

/**
 * File: core/config.php
 * Description: Application configuration (English, PHP 8+). Centralizes environment, database,
 *              and security settings without making any DB connections here.
 *
 * SECURITY NOTES:
 * - This file defines constants only; actual DB connections happen in core/classes/db.php.
 * - Do NOT commit real secrets to source control. Consider loading sensitive values from environment variables.
 * - In production, APP_DEBUG must be false to avoid leaking details.
 */

if (!defined('FastCore')) { define('FastCore', true); }

// =============================
// Environment
// =============================
// Allowed values: 'development' or 'production'
if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'production');
}

// Debug switch based on environment
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', APP_ENV === 'development');
}

// Application timezone
if (!defined('APP_TZ')) {
    define('APP_TZ', getenv('APP_TZ') ?: 'Asia/Tashkent');
}
date_default_timezone_set(APP_TZ);

// =============================
/* Database (no connection here) */
// =============================
// Prefer environment variables; fallback to hardcoded values.
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'fastcore_user');   // CHANGE ME
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: 'ChangeThisPassword'); // CHANGE ME
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'fastcore');

// Optional: DSN socket override (if using unix_socket auth)
// define('DB_SOCKET', '/var/lib/mysql/mysql.sock'); // Example path; leave undefined to use TCP

// =============================
// Application URLs & Paths
// =============================
if (!defined('APP_URL')) {
    // If set in the environment, use it; otherwise let index.php compute BASE_URL at runtime.
    define('APP_URL', getenv('APP_URL') ?: '');
}

// Absolute path to project root (computed in index.php usually)
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// =============================
// Security / Sessions / CSRF
// =============================
if (!defined('SESSION_NAME')) {
    define('SESSION_NAME', 'fastcore_sid');
}
if (!defined('SESSION_SAMESITE')) {
    define('SESSION_SAMESITE', 'Lax'); // 'Lax' or 'Strict'
}
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 0); // session cookie
}

// CSRF token lifetime in seconds (e.g., 2 hours)
if (!defined('CSRF_TTL')) {
    define('CSRF_TTL', 7200);
}

// Crypto keys (for HMAC, encrypting tokens, etc.).
// DO NOT hardcode real secrets in source; load from env or a secure secrets store.
if (!defined('APP_KEY')) {
    define('APP_KEY', getenv('APP_KEY') ?: 'CHANGE_THIS_APP_KEY'); // 32+ random chars
}

// Password hashing options
if (!defined('PASSWORD_COST')) {
    define('PASSWORD_COST', 12); // for PASSWORD_BCRYPT
}

// =============================
// Mail (optional; adjust for your SMTP provider)
// =============================
if (!defined('MAIL_FROM')) define('MAIL_FROM', getenv('MAIL_FROM') ?: 'no-reply@example.com');
if (!defined('MAIL_HOST')) define('MAIL_HOST', getenv('MAIL_HOST') ?: '');
if (!defined('MAIL_USER')) define('MAIL_USER', getenv('MAIL_USER') ?: '');
if (!defined('MAIL_PASS')) define('MAIL_PASS', getenv('MAIL_PASS') ?: '');
if (!defined('MAIL_PORT')) define('MAIL_PORT', getenv('MAIL_PORT') ?: '');
if (!defined('MAIL_SECURE')) define('MAIL_SECURE', getenv('MAIL_SECURE') ?: 'tls'); // tls|ssl

// =============================
// Error display/logging (index.php should respect APP_DEBUG)
// =============================
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('display_startup_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

// If index.php hasn't set a log, choose a writable default under project root
if (!ini_get('error_log') || ini_get('error_log') === '') {
    $logDir = APP_ROOT . '/logs';
    if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
    ini_set('error_log', $logDir . '/php-error.log');
}

// =============================
// Admin URL prefix (routes depend on it)
// =============================
if (!defined('ADMIN_PREFIX')) {
    define('ADMIN_PREFIX', getenv('ADMIN_PREFIX') ?: 'admin');
}
