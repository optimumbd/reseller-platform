<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\SecurityHelper;

final class ApiToken extends BaseModel
{
    protected static string $table = 'api_tokens';

    public static function findByPlainToken(string $plain): ?self
    {
        return self::whereOne('token_hash = :h AND revoked_at IS NULL', ['h' => SecurityHelper::tokenHash($plain)]);
    }
}
