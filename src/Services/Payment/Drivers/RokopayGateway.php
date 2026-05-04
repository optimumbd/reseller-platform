<?php

declare(strict_types=1);

namespace App\Services\Payment\Drivers;

/**
 * RokoPay (https://rokopay.com) hosted-checkout driver.
 *
 * RokoPay re-uses the same white-label gateway product as Pay KureGhor:
 * identical request shape, identical headers (API-KEY / SECRET-KEY /
 * BRAND-KEY), identical /api/payment/create + /api/payment/verify
 * endpoints. Only the host differs — payments live at
 * https://pay.rokopay.com.
 *
 * Configuration lives under config('payments.gateways.rokopay'):
 *   api_key, secret_key, brand_key, optional base_url override.
 */
final class RokopayGateway extends PaykureghorGateway
{
    protected const PRODUCTION_BASE = 'https://pay.rokopay.com';

    public function name(): string
    {
        return 'rokopay';
    }

    protected function brandName(): string
    {
        return 'RokoPay';
    }
}
