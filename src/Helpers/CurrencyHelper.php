<?php

declare(strict_types=1);

namespace App\Helpers;

final class CurrencyHelper
{
    public static function format(float $amount, string $currency = 'USD'): string
    {
        $symbols = [
            'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'BDT' => '৳', 'INR' => '₹',
            'JPY' => '¥', 'CNY' => '¥', 'AUD' => 'A$', 'CAD' => 'C$',
        ];
        $symbol = $symbols[$currency] ?? ($currency . ' ');
        return $symbol . number_format($amount, 2);
    }

    public static function symbol(string $currency): string
    {
        return self::format(0, $currency)[0] ?? $currency;
    }
}
