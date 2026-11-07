<?php

namespace Tests\Unit;

use App\Support\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    /**
     * Test normal Egyptian mobile formats normalized to 11 digits: 01xxxxxxxxx.
     */
    public function test_normalizes_standard_egyptian_numbers(): void
    {
        $this->assertEquals('01012345678', PhoneNormalizer::normalizeEgyptianPhone('01012345678'));
        $this->assertEquals('01123456789', PhoneNormalizer::normalizeEgyptianPhone('01123456789'));
        $this->assertEquals('01234567890', PhoneNormalizer::normalizeEgyptianPhone('01234567890'));
        $this->assertEquals('01512345678', PhoneNormalizer::normalizeEgyptianPhone('01512345678'));
    }

    /**
     * Test stripping international prefix 002 or +20.
     */
    public function test_strips_international_prefixes(): void
    {
        $this->assertEquals('01012345678', PhoneNormalizer::normalizeEgyptianPhone('+201012345678'));
        $this->assertEquals('01012345678', PhoneNormalizer::normalizeEgyptianPhone('00201012345678'));
        $this->assertEquals('01012345678', PhoneNormalizer::normalizeEgyptianPhone('201012345678'));
    }

    /**
     * Test missing leading zero (10 digits starting with 10, 11, 12, 15).
     */
    public function test_adds_missing_leading_zero(): void
    {
        $this->assertEquals('01012345678', PhoneNormalizer::normalizeEgyptianPhone('1012345678'));
        $this->assertEquals('01123456789', PhoneNormalizer::normalizeEgyptianPhone('1123456789'));
    }

    /**
     * Test non-digit characters are cleaned (spaces, hyphens, brackets).
     */
    public function test_cleans_non_digit_characters(): void
    {
        $this->assertEquals('01012345678', PhoneNormalizer::normalizeEgyptianPhone('010-1234-5678'));
        $this->assertEquals('01012345678', PhoneNormalizer::normalizeEgyptianPhone(' (010) 1234 5678 '));
    }

    /**
     * Test international conversion for WhatsApp (201xxxxxxxxx).
     */
    public function test_converts_to_international_format(): void
    {
        $this->assertEquals('201012345678', PhoneNormalizer::toInternational('01012345678'));
        $this->assertEquals('201123456789', PhoneNormalizer::toInternational('1123456789'));
    }
}
