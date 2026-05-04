<?php

declare(strict_types=1);

namespace App\Models;

final class TldPricing extends BaseModel
{
    protected static string $table = 'tld_pricing';

    public static function forTld(string $tld): ?self
    {
        return self::whereOne('tld = :tld AND is_active = 1', ['tld' => ltrim($tld, '.')]);
    }
}
