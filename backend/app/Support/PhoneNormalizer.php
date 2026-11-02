<?php

namespace App\Support;

class PhoneNormalizer
{
    /**
     * Normalizes Egyptian mobile numbers into canonical 11-digit local format:
     * 010xxxxxxxx, 011xxxxxxxx, 012xxxxxxxx, 015xxxxxxxx
     */
    public static function normalizeEgyptianPhone(string $raw): string
    {
        // Strip non-digit characters
        $digits = preg_replace('/[^\d]/', '', $raw);

        // If international format: 00201xxxxxxxxx (14 digits)
        if (str_starts_with($digits, '00201') && strlen($digits) === 14) {
            return '0' . substr($digits, 4);
        }

        // If international format: 201xxxxxxxxx (12 digits)
        if (str_starts_with($digits, '201') && strlen($digits) === 12) {
            return '0' . substr($digits, 2);
        }

        // If entered without leading 0: 1xxxxxxxxx (10 digits)
        if (preg_match('/^1[0125]\d{8}$/', $digits)) {
            return '0' . $digits;
        }

        // Standard 11-digit local: 01xxxxxxxxx
        if (preg_match('/^01[0125]\d{8}$/', $digits)) {
            return $digits;
        }

        return $digits;
    }

    /**
     * Converts canonical Egyptian phone into international format: 201xxxxxxxxx (for WhatsApp)
     */
    public static function toInternational(string $phone): string
    {
        $normalized = self::normalizeEgyptianPhone($phone);
        if (str_starts_with($normalized, '0')) {
            return '2' . $normalized;
        }
        return $normalized;
    }
}
