<?php
/**
 * File: core/wallets.php
 * Purpose: Wallet validators/normalizers for Payeer, Qiwi, YooMoney
 */
declare(strict_types=1);

if (!defined('FastCore')) {
    http_response_code(403);
    exit('Выявлена попытка взлома!');
}

class wallets
{
    // Patterns
    private const RE_PAYEER        = '/^P[0-9]{7,12}$/';
    private const RE_QIWI_STRICT   = '/^\+79[0-9]{9}$/';
    private const RE_QIWI_FLEX     = '/^(?:7|8)9[0-9]{9}$/';  // will be normalized to +7XXXXXXXXXX
    private const RE_YOOMONEY      = '/^41001[0-9]{7,11}$/';

    /**
     * Normalize input: trim, uppercase, remove spaces, hyphens, parentheses, dots,
     * NBSP and zero-width spaces.
     */
    private function normalize(string $purse): string
    {
        $purse = trim($purse);
        $purse = strtoupper($purse);
        // Remove common separators and whitespace variants
        $purse = str_replace(
            ["\xC2\xA0", ' ', '-', '(', ')', '.', "\t", "\n", "\r"],
            '',
            $purse
        );
        // Remove zero-width spaces (ZWSP, ZWJ, ZWNJ, BOM)
        $purse = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $purse) ?? $purse;
        return $purse;
    }

    /**
     * Payeer: "P" + 7..12 digits
     * @return string|false
     */
    public function payeer_wallet($purse)
    {
        if (!is_string($purse)) return false;
        $purse = $this->normalize($purse);
        if (!preg_match(self::RE_PAYEER, $purse)) return false;
        return $purse;
    }

    /**
     * QIWI: strictly "+79XXXXXXXXX"
     * Also accepts "79XXXXXXXXX" or "89XXXXXXXXX" and normalizes to "+7XXXXXXXXXX"
     * @return string|false
     */
    public function qiwi_wallet($purse)
    {
        if (!is_string($purse)) return false;
        $raw = $this->normalize($purse);

        if (preg_match(self::RE_QIWI_STRICT, $raw)) {
            return $raw;
        }
        if (preg_match(self::RE_QIWI_FLEX, $raw)) {
            // Normalize to +7XXXXXXXXXX (keep last 10 digits after country code)
            return '+7' . substr($raw, -10);
        }
        return false;
    }

    /**
     * YooMoney: wallet number starting with 41001 + 7..11 digits
     * @return string|false
     */
    public function yoomoney_wallet($purse)
    {
        if (!is_string($purse)) return false;
        $purse = $this->normalize($purse);
        if (!preg_match(self::RE_YOOMONEY, $purse)) return false;
        return $purse;
    }

    /**
     * Generic validator by system name: 'payeer', 'qiwi', 'yoomoney'|'yandex'|'yad'
     * @return string|false
     */
    public function validate($system, $purse)
    {
        $system = strtolower((string)$system);
        switch ($system) {
            case 'payeer':
                return $this->payeer_wallet($purse);
            case 'qiwi':
                return $this->qiwi_wallet($purse);
            case 'yoomoney':
            case 'yandex':
            case 'yad':
                return $this->yoomoney_wallet($purse);
            default:
                return false;
        }
    }
}
