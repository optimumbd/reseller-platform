<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Order;

final class OrdersController extends BaseController
{
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->input('page', 1));
        $pagination = Order::paginate('1=1', [], $page, 25, 'id DESC', '/admin/orders');
        return $this->view('admin/orders/index', ['pagination' => $pagination]);
    }

    public function show(Request $request, int $id): Response
    {
        $order = Order::find($id);
        if (!$order) {
            return $this->view('errors/404', [], 404);
        }
        return $this->view('admin/orders/show', ['order' => $order]);
    }
}
