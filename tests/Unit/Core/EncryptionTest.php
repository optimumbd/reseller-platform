<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Encryption;
use PHPUnit\Framework\TestCase;

final class EncryptionTest extends TestCase
{
    public function test_key_must_be_at_least_32_bytes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Encryption('too-short');
    }

    public function test_round_trip_recovers_plaintext(): void
    {
        $enc = new Encryption(str_repeat('a', 32));
        $plain = 'sensitive credential value: åéü';
        $cipher = $enc->encrypt($plain);

        $this->assertNotSame($plain, $cipher);
        $this->assertSame($plain, $enc->decrypt($cipher));
    }

    public function test_each_encryption_produces_a_fresh_iv(): void
    {
        $enc = new Encryption(str_repeat('k', 32));
        $a = $enc->encrypt('hello');
        $b = $enc->encrypt('hello');

        $this->assertNotSame($a, $b);
        $this->assertSame('hello', $enc->decrypt($a));
        $this->assertSame('hello', $enc->decrypt($b));
    }

    public function test_decrypt_rejects_invalid_payload(): void
    {
        $enc = new Encryption(str_repeat('k', 32));
        $this->expectException(\RuntimeException::class);
        $enc->decrypt('not-base64-***');
    }

    public function test_from_app_key_strips_base64_prefix(): void
    {
        $key = Encryption::generateKey();
        $this->assertStringStartsWith('base64:', $key);
        $enc = Encryption::fromAppKey($key);
        $this->assertSame('roundtrip', $enc->decrypt($enc->encrypt('roundtrip')));
    }
}
