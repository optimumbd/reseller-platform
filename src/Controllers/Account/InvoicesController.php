<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\Payment\PaymentGatewayFactory;

final class InvoicesController extends BaseController
{
    public function index(Request $request): Response
    {
        $user = User::currentModel();
        $invoices = Invoice::where('user_id = :u ORDER BY id DESC', ['u' => (int) $user->id]);
        return $this->view('account/invoices/index', ['invoices' => $invoices]);
    }

    public function show(Request $request, int $id): Response
    {
        $user = User::currentModel();
        $invoice = Invoice::find($id);
        if (!$invoice || (int) $invoice->user_id !== (int) $user->id) {
            return $this->view('errors/404', [], 404);
        }
        $items = InvoiceItem::where('invoice_id = :i', ['i' => (int) $invoice->id]);
        $gateways = PaymentGatewayFactory::enabled();
        return $this->view('account/invoices/show', [
            'invoice' => $invoice,
            'items' => $items,
            'gateways' => $gateways,
        ]);
    }

    public function pay(Request $request, int $id): Response
    {
        $user = User::currentModel();
        $invoice = Invoice::find($id);
        if (!$invoice || (int) $invoice->user_id !== (int) $user->id) {
            return $this->view('errors/404', [], 404);
        }
        $gateway = (string) $request->input('gateway', 'manual');
        $driver = PaymentGatewayFactory::make($gateway);
        $result = $driver->createCheckout($invoice);
        if (!($result['success'] ?? false)) {
            flash('error', $result['error'] ?? 'Could not start checkout');
            return $this->back();
        }
        return $this->redirect((string) ($result['redirect_url'] ?? '/account/invoices/' . $id));
    }
}
