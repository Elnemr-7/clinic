<?php

namespace Tests\Unit;

use App\Services\WhatsAppTokenService;
use PHPUnit\Framework\TestCase;

class WhatsAppTokenServiceTest extends TestCase
{
    protected string $testSecret = 'secure_unit_test_secret_key_2026';

    /**
     * Test generated token matches timestamp.signature structure.
     */
    public function test_generated_token_structure(): void
    {
        $token = WhatsAppTokenService::generateToken($this->testSecret);

        $parts = explode('.', $token);
        $this->assertCount(2, $parts);
        $this->assertIsNumeric($parts[0]);
        $this->assertEquals(64, strlen($parts[1])); // SHA-256 hex string length
    }

    /**
     * Test freshly generated token verifies successfully.
     */
    public function test_fresh_token_verifies(): void
    {
        $token = WhatsAppTokenService::generateToken($this->testSecret);
        $this->assertTrue(WhatsAppTokenService::verifyToken($token, $this->testSecret));
    }

    /**
     * Test token fails verification if signature is tampered.
     */
    public function test_tampered_token_fails(): void
    {
        $token = WhatsAppTokenService::generateToken($this->testSecret);
        $parts = explode('.', $token);
        $tampered = $parts[0] . '.' . substr_replace($parts[1], 'ff', 0, 2);

        $this->assertFalse(WhatsAppTokenService::verifyToken($tampered, $this->testSecret));
    }

    /**
     * Test token fails verification if timestamp is older than 5 minutes (300 seconds).
     */
    public function test_expired_token_fails(): void
    {
        $oldTimestamp = time() - 305; // 5 minutes 5 seconds ago
        $sig = hash_hmac('sha256', "{$oldTimestamp}:{$this->testSecret}", $this->testSecret);
        $expiredToken = "{$oldTimestamp}.{$sig}";

        $this->assertFalse(WhatsAppTokenService::verifyToken($expiredToken, $this->testSecret));
    }

    /**
     * Test invalid or empty token inputs safely return false without throwing.
     */
    public function test_empty_or_malformed_token_returns_false(): void
    {
        $this->assertFalse(WhatsAppTokenService::verifyToken(null, $this->testSecret));
        $this->assertFalse(WhatsAppTokenService::verifyToken('', $this->testSecret));
        $this->assertFalse(WhatsAppTokenService::verifyToken('invalid-format', $this->testSecret));
        $this->assertFalse(WhatsAppTokenService::verifyToken('abc.def.ghi', $this->testSecret));
    }
}
