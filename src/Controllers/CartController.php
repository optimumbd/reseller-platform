<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\TldPricing;

final class CartController extends BaseController
{
    public function index(Request $request): Response
    {
        $cart = $this->getCart();
        return $this->view('cart/index', ['cart' => $cart]);
    }

    public function add(Request $request): Response
    {
        $cart = $this->getCart();
        $type = (string) $request->input('type', 'domain');
        $domain = strtolower(trim((string) $request->input('domain', '')));
        $years = max(1, (int) $request->input('years', 1));
        if ($type === 'domain' && $domain !== '') {
            $tld = substr($domain, strpos($domain, '.') + 1);
            $pricing = TldPricing::whereOne('tld = :t', ['t' => $tld]);
            $price = $pricing ? (float) $pricing->register_price : 9.99;
            $cart['items'][] = [
                'type' => 'domain',
                'name' => $domain,
                'years' => $years,
                'price' => $price,
                'currency' => $pricing ? (string) $pricing->currency : 'USD',
            ];
        }
        $this->saveCart($cart);
        if ($request->expectsJson()) {
            return $this->json(['ok' => true, 'cart' => $cart]);
        }
        flash('success', __('cart.added'));
        return $this->redirect('/cart');
    }

    public function remove(Request $request, string|int $index): Response
    {
        $cart = $this->getCart();
        $i = (int) $index;
        if (isset($cart['items'][$i])) {
            array_splice($cart['items'], $i, 1);
            $this->saveCart($cart);
        }
        return $this->redirect('/cart');
    }

    public function update(Request $request): Response
    {
        $cart = $this->getCart();
        $items = (array) $request->input('items', []);
        foreach ($items as $i => $update) {
            if (isset($cart['items'][(int) $i]) && isset($update['years'])) {
                $cart['items'][(int) $i]['years'] = max(1, (int) $update['years']);
            }
        }
        $this->saveCart($cart);
        return $this->redirect('/cart');
    }

    public function applyCoupon(Request $request): Response
    {
        $cart = $this->getCart();
        $cart['coupon'] = strtoupper(trim((string) $request->input('code', '')));
        $this->saveCart($cart);
        flash('info', __('cart.coupon_applied'));
        return $this->redirect('/cart');
    }

    private function getCart(): array
    {
        return (array) ($this->app()->session->get('cart') ?? ['items' => [], 'coupon' => null]);
    }

    private function saveCart(array $cart): void
    {
        $this->app()->session->put('cart', $cart);
    }
}
