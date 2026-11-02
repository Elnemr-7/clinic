<?php

namespace App\Services;

class WhatsAppTokenService
{
    protected static function getSecret(?string $secret = null): string
    {
        return $secret ?? config('services.whatsapp.secret', 'vertex_wa_secure_secret_key_2026');
    }

    public static function generateToken(?string $secret = null): string
    {
        $resolvedSecret = self::getSecret($secret);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}:{$resolvedSecret}", $resolvedSecret);
        return "{$timestamp}.{$signature}";
    }

    public static function verifyToken(?string $token, ?string $secret = null): bool
    {
        if (empty($token) || !is_string($token)) {
            return false;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return false;
        }

        [$timestampStr, $providedSignature] = $parts;
        $timestamp = (int)$timestampStr;
        if (!$timestamp) {
            return false;
        }

        $now = time();
        if (abs($now - $timestamp) > 300) { // 5 minutes window
            return false;
        }

        $resolvedSecret = self::getSecret($secret);
        $expectedSignature = hash_hmac('sha256', "{$timestamp}:{$resolvedSecret}", $resolvedSecret);

        return hash_equals($expectedSignature, $providedSignature);
    }

    public static function getRequestHeaders(?string $secret = null): array
    {
        $token = self::generateToken($secret);
        $resolvedSecret = self::getSecret($secret);

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Server-Token' => $token,
            'X-Shared-Secret' => $resolvedSecret,
            'Authorization' => "Bearer {$token}",
        ];
    }
}
