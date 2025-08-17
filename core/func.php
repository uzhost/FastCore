<?php
/**
 * File: core/func.php
 * Description: Common helper functions for FastCore (English, PHP 8+ compatible).
 * Notes:
 *  - Removed unparenthesized nested ternaries (PHP 8 requirement).
 *  - Hardened input validation for passwords and emails.
 *  - Kept the original class name (`func`) and method names/signatures for compatibility.
 */

if (!defined('FastCore')) { exit('Oops!'); }

class func {

    // ===================================
    // Profit collection calculator
    // per_h: income per hour (numeric, >= 0)
    // sum_tree: (kept for compatibility; not used in original math beyond formatting)
    // last_sbor: Unix timestamp of last collection
    // Returns: float amount accrued since last_sbor
    // ===================================
    public function SumCalc($per_h, $sum_tree, $last_sbor) {
        // Normalize/validate
        if (!is_numeric($per_h) || !is_numeric($sum_tree) || !is_numeric($last_sbor)) {
            return 0;
        }
        $per_h = (float)$per_h;
        $sum_tree = (float)$sum_tree;
        $last_sbor = (int)$last_sbor;

        if ($last_sbor <= 0) {
            return 0;
        }
        if ($sum_tree <= 0 || $per_h <= 0) {
            return 0;
        }

        // Time delta in seconds (future timestamps guard)
        $seconds = ($last_sbor < time()) ? (time() - $last_sbor) : 0;

        // Convert hourly rate to per-second and multiply by elapsed seconds
        $accrued = ($per_h / 3600.0) * $seconds;

        // 4 decimal places like original
        return round($accrued, 4);
    }

    // ===================================
    // Password filter/validator
    // $mask: character class (without surrounding / /), default includes Latin/Cyrillic letters, digits and some symbols
    // $len: length quantifier, default {4,20}
    // Returns: sanitized password string or false
    // ===================================
    public function FPass($pass, $mask = "^[!@#$%*а-яА-ЯЁёa-zA-Z0-9_]", $len = "{4,20}") {
        if (is_array($pass)) {
            return false;
        }
        $pass = (string)$pass;

        // Build regex safely
        $pattern = "/{$mask}{$len}$/u";

        // Explicit ternary associativity for PHP 8+
        if (preg_match($pattern, $pass)) {
            return $pass;
        }
        return false;
    }

    // ===================================
    // Email filter/validator
    // - Uses filter_var for RFC-aware validation.
    // - Enforces local part <= 64 chars; total length <= 255.
    // Returns: lowercased email or false
    // ===================================
    public function FMail($email) {
        if (is_array($email)) {
            return false;
        }
        $email = trim((string)$email);

        // Basic length constraints
        if ($email === '' || strlen($email) > 255) {
            return false;
        }

        // Validate structure
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Local part length check
        $atPos = strpos($email, '@');
        if ($atPos === false || $atPos > 64) {
            // Either no @, or local part > 64 characters
            return false;
        }

        return strtolower($email);
    }
}
