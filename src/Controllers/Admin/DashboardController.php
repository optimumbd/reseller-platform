<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;

final class DashboardController extends BaseController
{
    public function index(Request $request): Response
    {
        $stats = [
            'users' => User::count(),
            'domains' => Domain::count(),
            'services' => Service::count(),
            'open_tickets' => Ticket::count('status = "open"'),
            'unpaid_invoices' => Invoice::count('status = "unpaid"'),
            'orders_today' => Order::count('DATE(created_at) = CURDATE()'),
        ];
        $recentOrders = Order::where('1=1 ORDER BY id DESC LIMIT 10');
        $recentUsers = User::where('1=1 ORDER BY id DESC LIMIT 10');
        return $this->view('admin/dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'recentUsers' => $recentUsers,
        ]);
    }
}
