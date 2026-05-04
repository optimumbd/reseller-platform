<?php

declare(strict_types=1);

namespace App\Models;

final class Wallet extends BaseModel
{
    protected static string $table = 'wallets';

    public static function forUser(int $userId): self
    {
        $w = self::whereOne('user_id = :uid', ['uid' => $userId]);
        if ($w) {
            return $w;
        }
        $w = new self(['user_id' => $userId, 'balance' => 0, 'currency' => 'USD']);
        $w->save();
        return $w;
    }
}
