<?php
declare(strict_types=1);

/**
 * File: core/func.php
 * Purpose: Common helper utilities for FastCore (PHP 8+).
 * Notes:
 *  - Adds security-focused helpers and sane defaults.
 *  - Provides BASE_URL computation and legacy-compat shims.
 *  - Methods are static; instance calls are routed via __call for BC.
 */

if (!defined('FastCore')) {
    exit('Oops!');
}

/**
 * Global HTML escaper.
 * Accepts any scalar; casts to string and escapes safely.
 * Prevents "must be string|null" fatals if numbers are passed.
 */
if (!function_exists('h')) {
    function h($value): string {
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_array($value) || is_object($value)) {
            // Avoid leaking structure; developer can json_encode explicitly when needed
            $value = '[object]';
        }
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

final class func
{
    /* ============================
     * Legacy compatibility shims
     * ============================ */

    /**
     * Route instance method calls to static implementations to preserve
     * compatibility with older code that used $f = new func; $f->FPass(...).
     */
    public function __call(string $name, array $args) {
        if (method_exists(self::class, $name)) {
            // Call the static method with the same name
            return forward_static_call_array([self::class, $name], $args);
        }
        throw new Error('Call to undefined method ' . __CLASS__ . "::$name()");
    }

    /**
     * Gracefully handle static calls if a non-static variant existed before.
     */
    public static function __callStatic(string $name, array $args) {
        if (method_exists(self::class, $name)) {
            return forward_static_call_array([self::class, $name], $args);
        }
        throw new Error('Call to undefined method ' . __CLASS__ . "::$name()");
    }

    /* ============================
     * Environment / URL helpers
     * ============================ */

    /** Detect HTTPS considering reverse proxies. */
    public static function isHttps(): bool {
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https') {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
            return true;
        }
        if (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        return false;
    }

    /** Return the current host (punycode left as-is). */
    public static function getHost(): string {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

        // Remove whitespace/control characters
        $host = preg_replace('/[^\P{C}\s]+/u', '', (string)$host) ?? 'localhost';
        $host = trim($host);

        // Basic allowlist: letters, digits, dot, dash, colon (for ports)
        if (!preg_match('/^[A-Za-z0-9\.\-:]+$/', $host)) {
            $host = 'localhost';
        }
        return $host;
    }

    /**
     * Compute base URL scheme+host[:port].
     * Reuses BASE_URL if defined by the front controller.
     */
    public static function getBaseUrl(): string {
        if (defined('BASE_URL') && is_string(BASE_URL)) {
            return BASE_URL;
        }
        $scheme = self::isHttps() ? 'https' : 'http';
        $host   = self::getHost();

        // Append non-default port if present
        if (strpos($host, ':') === false && !empty($_SERVER['SERVER_PORT'])) {
            $port = (int)$_SERVER['SERVER_PORT'];
            $isDefault = ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80);
            if (!$isDefault) {
                $host .= ':' . $port;
            }
        }
        return $scheme . '://' . $host;
    }

    /**
     * Attempt to extract the registrable domain portion.
     * (Lightweight heuristic; for full accuracy use a PSL library.)
     */
    public static function getDomain(): string {
        $host = self::getHost();
        // If an IPv6/IPv4 or localhost, just return host
        if (filter_var($host, FILTER_VALIDATE_IP) || $host === 'localhost') {
            return $host;
        }
        $parts = explode('.', $host);
        if (count($parts) >= 2) {
            $lastTwo = implode('.', array_slice($parts, -2));
            return $lastTwo;
        }
        return $host;
    }

    /** Safe redirect helper. */
    public static function redirect(string $url, int $code = 302): never {
        // Prevent header injection
        if (!preg_match('/^https?:\/\//i', $url) && !str_starts_with($url, '/')) {
            $url = '/';
        }
        header('Location: ' . $url, true, $code);
        exit;
    }

    /* ============================
     * Request helpers
     * ============================ */

    public static function isPost(): bool {
        return (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST');
    }

    public static function getParam(string $key, mixed $default = null): mixed {
        return $_GET[$key] ?? $default;
    }

    public static function postParam(string $key, mixed $default = null): mixed {
        return $_POST[$key] ?? $default;
    }

    /** Mild string sanitizer (trim + strip NULL bytes). */
    public static function sanitizeString(?string $s): string {
        $s = (string)($s ?? '');
        $s = str_replace("\0", '', $s);
        return trim($s);
    }

    /* ============================
     * Security helpers
     * ============================ */

    /** Generate (and cache) a CSRF token in the session. */
    public static function csrfToken(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // Avoid changing cookie params here; let front controller decide
            @session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['csrf_token'];
    }

    /** Hidden input for CSRF token. */
    public static function csrfInput(string $name = 'csrf_token'): string {
        return '<input type="hidden" name="' . h($name) . '" value="' . h(self::csrfToken()) . '">';
    }

    /** Validate a provided CSRF token value. */
    public static function csrfValidate(?string $token): bool {
        $known = self::csrfToken();
        $user  = (string)($token ?? '');
        return self::timingSafeEquals($known, $user);
    }

    /** Timing-safe string comparison. */
    public static function timingSafeEquals(string $a, string $b): bool {
        if (function_exists('hash_equals')) {
            return hash_equals($a, $b);
        }
        // Fallback
        $len = max(strlen($a), strlen($b));
        $diff = 0;
        for ($i = 0; $i < $len; $i++) {
            $diff |= ord($a[$i] ?? "\0") ^ ord($b[$i] ?? "\0");
        }
        return $diff === 0;
    }

    /** Random URL-safe string. */
    public static function randomString(int $length = 32): string {
        if ($length < 1) $length = 32;
        $bytes = random_bytes((int)ceil($length * 0.75));
        $str = rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
        return substr($str, 0, $length);
    }

    /** Best-effort client IP detection (validates each hop). */
    public static function clientIp(): string {
        $candidates = [];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $ip) {
                $candidates[] = trim($ip);
            }
        }
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $candidates[] = trim((string)$_SERVER['HTTP_CLIENT_IP']);
        }
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $candidates[] = trim((string)$_SERVER['REMOTE_ADDR']);
        }
        foreach ($candidates as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE)) {
                return $ip;
            }
        }
        // Fallback: allow private ranges if nothing else validated
        foreach ($candidates as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '0.0.0.0';
    }

    /* ============================
     * Legacy helpers (kept)
     * ============================ */

    /**
     * Profit collection calculator.
     * per_h: income per hour (>=0); sum_tree kept for BC; last_sbor: unix timestamp.
     * Returns amount accrued since last_sbor (4 dp).
     */
    public static function SumCalc($per_h, $sum_tree, $last_sbor): float {
        if (!is_numeric($per_h) || !is_numeric($sum_tree) || !is_numeric($last_sbor)) {
            return 0.0;
        }
        $per_h     = (float)$per_h;
        $sum_tree  = (float)$sum_tree;
        $last_sbor = (int)$last_sbor;

        if ($last_sbor <= 0 || $sum_tree <= 0 || $per_h <= 0) {
            return 0.0;
        }
        $seconds = ($last_sbor < time()) ? (time() - $last_sbor) : 0;
        $accrued = ($per_h / 3600.0) * $seconds;
        return round($accrued, 4);
    }

    /**
     * Password validator.
     * Default: letters (Latin/Cyrillic), digits, and !@#$%*_.  length 4–64.
     * Returns the original string on success, or false on failure.
     */
    public static function FPass($pass, string $mask = '^[!@#$%*_.а-яА-ЯЁёa-zA-Z0-9-]', string $len = '{4,64}') {
        if (is_array($pass)) return false;
        $pass = (string)$pass;

        // Build regex safely
        $pattern = "/{$mask}{$len}$/u";

        if (preg_match($pattern, $pass)) {
            return $pass;
        }
        return false;
    }

    /**
     * Email validator (RFC-aware via filter_var).
     * Enforces local part <= 64, total <= 255; returns lowercase or false.
     */
    public static function FMail($email) {
        if (is_array($email)) return false;
        $email = trim((string)$email);

        if ($email === '' || strlen($email) > 255) return false;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;

        $atPos = strpos($email, '@');
        if ($atPos === false || $atPos > 64) return false;

        return strtolower($email);
    }

    /* ============================
     * Formatting helpers
     * ============================ */

    /** Money formatting helper with dot decimals and space thousands. */
    public static function moneyFormat(float $amount, int $decimals = 2): string {
        return number_format($amount, $decimals, '.', ' ');
    }
}
