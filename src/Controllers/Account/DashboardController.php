<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\User;

final class DashboardController extends BaseController
{
    public function index(Request $request): Response
    {
        $user = User::currentModel();
        $stats = [
            'services' => Service::count('user_id = :u', ['u' => (int) $user->id]),
            'domains' => Domain::count('user_id = :u', ['u' => (int) $user->id]),
            'invoices_due' => Invoice::count('user_id = :u AND status = "unpaid"', ['u' => (int) $user->id]),
        ];
        $recentInvoices = Invoice::where('user_id = :u ORDER BY id DESC LIMIT 5', ['u' => (int) $user->id]);
        $services = Service::where('user_id = :u ORDER BY id DESC LIMIT 10', ['u' => (int) $user->id]);
        return $this->view('account/dashboard', [
            'user' => $user,
            'stats' => $stats,
            'invoices' => $recentInvoices,
            'services' => $services,
        ]);
    }
}
