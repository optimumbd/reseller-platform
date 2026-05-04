<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\App;

final class Cart extends BaseModel
{
    protected static string $table = 'carts';

    /**
     * Get or create the cart for the current user/session.
     */
    public static function current(): self
    {
        $session = App::getInstance()->session;
        $session->start();
        $userId = $session->userId();
        $sid = session_id() ?: 'guest-' . str_random(16);

        if ($userId !== null) {
            $cart = self::whereOne('user_id = :uid ORDER BY id DESC', ['uid' => $userId]);
            if ($cart) {
                return $cart;
            }
        }
        $cart = self::whereOne('session_id = :sid ORDER BY id DESC', ['sid' => $sid]);
        if ($cart) {
            return $cart;
        }
        $cart = new self([
            'user_id' => $userId,
            'session_id' => $sid,
            'currency' => current_currency(),
        ]);
        $cart->save();
        return $cart;
    }

    /** @return CartItem[] */
    public function items(): array
    {
        return CartItem::where('cart_id = :cid ORDER BY id ASC', ['cid' => $this->id]);
    }

    public function subtotal(): float
    {
        $sum = 0.0;
        foreach ($this->items() as $item) {
            $sum += (float) $item->unit_price * (int) $item->quantity * max(1, (int) $item->years) + (float) $item->setup_fee;
        }
        return round($sum, 2);
    }
}
