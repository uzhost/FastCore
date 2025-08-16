<?php
/** 
 * File: core/config.php
 * Purpose: App configuration & DB bootstrap (safe defaults + .env overrides)
 */
declare(strict_types=1);

if (!defined('FastCore')) {
    exit('Oops!');
}

/* -------------------------------------------------------
 |  Optional: load .env from project root if present
 |  Supports KEY=VALUE lines, ignores # comments.
 ------------------------------------------------------- */
(function (): void {
    $envPath = dirname(__DIR__) . '/.env';
    if (!is_file($envPath) || !is_readable($envPath)) return;

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        if ($line[0] === '#') continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        // Strip optional quotes
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
            $val = substr($val, 1, -1);
        }
        if ($key !== '') {
            putenv("$key=$val");
            $_ENV[$key]    = $val;
            $_SERVER[$key] = $val;
        }
    }
})();

/* -------------------------------------------------------
 |  Database constants (env overrides; keep original names)
 |  Use: dbHost, dbUser, dbPass, dbName
 ------------------------------------------------------- */
if (!defined('dbHost')) define('dbHost', getenv('DB_HOST') ?: 'localhost');
if (!defined('dbUser')) define('dbUser', getenv('DB_USER') ?: 'db_user');
if (!defined('dbPass')) define('dbPass', getenv('DB_PASS') ?: 'db_pass');
if (!defined('dbName')) define('dbName', getenv('DB_NAME') ?: 'db_name');

/* -------------------------------------------------------
 |  DB connection (safe include + utf8mb4 by default)
 ------------------------------------------------------- */
require_once __DIR__ . '/classes/db.php';
$db = new db(dbHost, dbUser, dbPass, dbName);  // charset default is utf8mb4 in enhanced db.php

/* -------------------------------------------------------
 |  App config object
 |  - Keeps original public property names for compatibility
 |  - Allows .env overrides for secrets and settings
 |  - Adds minor normalization/validation
 ------------------------------------------------------- */
final class config
{
    # Site settings
    public string $start_time   = '1715363489';
    public string $sitename     = 'FASTCOIN';
    public string $email        = 'support@fastcoin.info';
    public string $email_domain = 'fastcore.info';

    # Admin (panel)
    public string $adm_dir  = 'admin';
    public string $adm_name = 'admin';
    public string $adm_pass = '123456';        // plaintext fallback (kept for BC)
    public ?string $adm_pass_hash = null;      // optional bcrypt/argon2 hash via ENV

    # PAYEER
    public string $py_shop   = '1111';
    public string $py_secret = '1111';
    public string $py_NUM    = 'P1234567';
    public string $py_apiID  = '1234567890';
    public string $py_apiKEY = '9876543210';

    # FREEKASSA
    public string $fk_id   = '1111';
    public string $fk_key  = '1111';
    public string $fk_key2 = '2222';

    public function __construct()
    {
        // Allow environment overrides without breaking existing code.
        $this->sitename     = getenv('APP_NAME')       ?: $this->sitename;
        $this->start_time   = getenv('APP_START_TIME') ?: $this->start_time;
        $this->email        = getenv('APP_EMAIL')      ?: $this->email;
        $this->email_domain = getenv('APP_EMAIL_DOMAIN') ?: $this->email_domain;

        $this->adm_dir      = getenv('ADMIN_DIR')      ?: $this->adm_dir;
        $this->adm_name     = getenv('ADMIN_USER')     ?: $this->adm_name;
        $this->adm_pass     = getenv('ADMIN_PASS')     ?: $this->adm_pass;           // plaintext fallback
        $this->adm_pass_hash= getenv('ADMIN_PASS_HASH')?: $this->adm_pass_hash;      // preferred

        $this->py_shop   = getenv('PAYEER_SHOP')    ?: $this->py_shop;
        $this->py_secret = getenv('PAYEER_SECRET')  ?: $this->py_secret;
        $this->py_NUM    = getenv('PAYEER_WALLET')  ?: $this->py_NUM;
        $this->py_apiID  = getenv('PAYEER_API_ID')  ?: $this->py_apiID;
        $this->py_apiKEY = getenv('PAYEER_API_KEY') ?: $this->py_apiKEY;

        $this->fk_id   = getenv('FK_ID')    ?: $this->fk_id;
        $this->fk_key  = getenv('FK_KEY1')  ?: $this->fk_key;
        $this->fk_key2 = getenv('FK_KEY2')  ?: $this->fk_key2;

        $this->normalize();
        $this->validateSoft();
    }

    /** Optional helper if you migrate admin password to a hash */
    public function isAdminPasswordValid(string $password): bool
    {
        if (!empty($this->adm_pass_hash)) {
            return password_verify($password, $this->adm_pass_hash);
        }
        // Backward compatibility (plaintext); consider removing later
        return hash_equals($this->adm_pass, $password);
    }

    private function normalize(): void
    {
        // Derive email_domain if empty and email has domain part
        if (empty($this->email_domain) && str_contains($this->email, '@')) {
            $domain = substr(strrchr($this->email, '@') ?: '', 1);
            if ($domain !== '') $this->email_domain = $domain;
        }

        // Sanitize admin dir (basic)
        $this->adm_dir = trim($this->adm_dir, "/ \t\n\r\0\x0B");
        if ($this->adm_dir === '') $this->adm_dir = 'admin';
    }

    private function validateSoft(): void
    {
        // Non-fatal checks -> log only
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            error_log('[config] Invalid APP_EMAIL: ' . $this->email);
        }
        // Warn if still using placeholder secrets in production
        $placeholders = ['1111', '123456', '9876543210', 'P1234567'];
        foreach ([
            'ADMIN_PASS' => $this->adm_pass,
            'PAYEER_SECRET' => $this->py_secret,
            'FK_KEY1' => $this->fk_key,
            'FK_KEY2' => $this->fk_key2,
        ] as $k => $v) {
            if (in_array($v, $placeholders, true)) {
                error_log("[config] Warning: $k appears to be a placeholder. Set real secrets in .env");
            }
        }
    }
}
