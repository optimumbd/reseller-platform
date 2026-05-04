<?php

declare(strict_types=1);

namespace App\Models;

final class EmailTemplate extends BaseModel
{
    protected static string $table = 'email_templates';

    public static function findByKey(string $key): ?self
    {
        return self::whereOne('`key` = :k', ['k' => $key]);
    }
}
