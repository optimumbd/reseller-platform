<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Order;
use App\Models\User;

final class OrderController extends BaseController
{
    public function index(Request $request): Response
    {
        $user = User::currentModel();
        if (!$user) {
            return $this->json(['error' => 'unauthorized'], 401);
        }
        $orders = Order::where('user_id = :u ORDER BY id DESC', ['u' => (int) $user->id]);
        return $this->json(['data' => array_map(fn ($o) => $o->toArray(), $orders)]);
    }
}
