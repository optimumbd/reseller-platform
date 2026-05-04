<?php

declare(strict_types=1);

namespace App\Models;

final class Order extends BaseModel
{
    protected static string $table = 'orders';

    public static function generateNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    /** @return OrderItem[] */
    public function items(): array
    {
        return OrderItem::where('order_id = :oid', ['oid' => $this->id]);
    }
}
