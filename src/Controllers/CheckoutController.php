<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Payment\PaymentGatewayFactory;

final class CheckoutController extends BaseController
{
    public function index(Request $request): Response
    {
        $cart = (array) ($this->app()->session->get('cart') ?? ['items' => []]);
        if (empty($cart['items'])) {
            flash('info', __('checkout.cart_empty'));
            return $this->redirect('/cart');
        }
        $gateways = PaymentGatewayFactory::enabled();
        return $this->view('checkout/index', ['cart' => $cart, 'gateways' => $gateways]);
    }

    public function place(Request $request): Response
    {
        $user = User::currentModel();
        if (!$user) {
            $this->app()->session->flash('intended', '/checkout');
            return $this->redirect('/login');
        }
        $cart = (array) ($this->app()->session->get('cart') ?? ['items' => []]);
        if (empty($cart['items'])) {
            return $this->redirect('/cart');
        }
        $currency = $cart['items'][0]['currency'] ?? 'USD';
        $subtotal = 0.0;
        foreach ($cart['items'] as $item) {
            $subtotal += ((float) $item['price']) * (int) ($item['years'] ?? 1);
        }
        $total = $subtotal;

        $order = new Order([
            'user_id' => (int) $user->id,
            'number' => 'ORD-' . strtoupper(bin2hex(random_bytes(4))),
            'status' => 'pending',
            'subtotal' => $subtotal,
            'tax' => 0,
            'discount' => 0,
            'total' => $total,
            'currency' => $currency,
        ]);
        $order->save();
        foreach ($cart['items'] as $item) {
            $oi = new OrderItem([
                'order_id' => (int) $order->id,
                'type' => $item['type'],
                'name' => $item['name'],
                'quantity' => 1,
                'years' => (int) ($item['years'] ?? 1),
                'price' => (float) $item['price'],
                'total' => ((float) $item['price']) * (int) ($item['years'] ?? 1),
            ]);
            $oi->save();
        }
        $invoice = new Invoice([
            'user_id' => (int) $user->id,
            'order_id' => (int) $order->id,
            'number' => 'INV-' . strtoupper(bin2hex(random_bytes(4))),
            'status' => 'unpaid',
            'subtotal' => $subtotal,
            'tax' => 0,
            'total' => $total,
            'paid_amount' => 0,
            'currency' => $currency,
            'due_at' => date('Y-m-d H:i:s', time() + 7 * 86400),
        ]);
        $invoice->save();
        foreach ($cart['items'] as $item) {
            $ii = new InvoiceItem([
                'invoice_id' => (int) $invoice->id,
                'description' => ucfirst((string) $item['type']) . ' ' . $item['name'] . ' × ' . ($item['years'] ?? 1) . ' yr',
                'quantity' => 1,
                'price' => (float) $item['price'],
                'total' => ((float) $item['price']) * (int) ($item['years'] ?? 1),
            ]);
            $ii->save();
        }
        $this->app()->session->forget('cart');

        $gateway = (string) $request->input('gateway', 'manual');
        $driver = PaymentGatewayFactory::make($gateway);
        $result = $driver->createCheckout($invoice);
        if (!($result['success'] ?? false)) {
            flash('error', $result['error'] ?? 'Could not start checkout');
            return $this->redirect('/account/invoices/' . $invoice->id);
        }
        return $this->redirect((string) ($result['redirect_url'] ?? ('/account/invoices/' . $invoice->id)));
    }
}
