<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Invoice;

final class InvoicesController extends BaseController
{
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->input('page', 1));
        $pagination = Invoice::paginate('1=1', [], $page, 25, 'id DESC', '/admin/invoices');
        return $this->view('admin/invoices/index', ['pagination' => $pagination]);
    }

    public function show(Request $request, int $id): Response
    {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return $this->view('errors/404', [], 404);
        }
        return $this->view('admin/invoices/show', ['invoice' => $invoice]);
    }
}
