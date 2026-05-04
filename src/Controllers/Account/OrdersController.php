<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;

final class OrdersController extends BaseController
{
    public function index(Request $request): Response
    {
        $user = User::currentModel();
        $orders = Order::where('user_id = :u ORDER BY id DESC', ['u' => (int) $user->id]);
        return $this->view('account/orders/index', ['orders' => $orders]);
    }

    public function show(Request $request, int $id): Response
    {
        $user = User::currentModel();
        $order = Order::find($id);
        if (!$order || (int) $order->user_id !== (int) $user->id) {
            return $this->view('errors/404', [], 404);
        }
        $items = OrderItem::where('order_id = :o', ['o' => (int) $order->id]);
        return $this->view('account/orders/show', ['order' => $order, 'items' => $items]);
    }
}
