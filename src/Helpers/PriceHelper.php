<?php

declare(strict_types=1);

namespace App\Helpers;

final class PriceHelper
{
    public static function format(float $amount, string $currency = 'USD'): string
    {
        return CurrencyHelper::format($amount, $currency);
    }

    public static function applyTax(float $amount, float $taxPercent): float
    {
        return round($amount + ($amount * $taxPercent / 100), 2);
    }

    public static function applyDiscount(float $amount, float $discountPercent): float
    {
        return round(max(0, $amount - ($amount * $discountPercent / 100)), 2);
    }
}
