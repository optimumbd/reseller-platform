<?php

declare(strict_types=1);

namespace App\Helpers;

final class SecurityHelper
{
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function constantTimeEquals(string $a, string $b): bool
    {
        return hash_equals($a, $b);
    }

    public static function tokenHash(string $token): string
    {
        return hash('sha256', $token);
    }
}
