<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Services\Security\TotpService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class TotpServiceTest extends TestCase
{
    public function test_generate_secret_returns_base32(): void
    {
        $secret = TotpService::generateSecret();
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
        $this->assertGreaterThan(20, strlen($secret));
    }

    public function test_round_trip_verify_for_current_window(): void
    {
        $secret = TotpService::generateSecret();
        $now = (int) floor(time() / 30);
        $code = TotpService::generateCode($secret, $now);
        $this->assertTrue(TotpService::verify($secret, $code));
    }

    public function test_verify_rejects_obviously_wrong_codes(): void
    {
        $secret = TotpService::generateSecret();
        $this->assertFalse(TotpService::verify($secret, 'abc'));
        $this->assertFalse(TotpService::verify($secret, '999999'));
    }

    public function test_verify_outside_window_fails(): void
    {
        $secret = TotpService::generateSecret();
        $way_in_past = (int) floor(time() / 30) - 100;
        $oldCode = TotpService::generateCode($secret, $way_in_past);
        $this->assertFalse(TotpService::verify($secret, $oldCode));
    }
}
