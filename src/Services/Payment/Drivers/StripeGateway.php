<?php

declare(strict_types=1);

namespace App\Services\Payment\Drivers;

use App\Core\Http\Client;
use App\Core\Http\HttpClientInterface;
use App\Core\Http\Response;
use App\Models\Invoice;
use App\Services\Payment\Contracts\PaymentGatewayInterface;

/**
 * Stripe payment gateway driver.
 *
 * Implements:
 *  - Checkout Sessions (`POST /v1/checkout/sessions`)
 *  - Payment Intent capture (`POST /v1/payment_intents/{id}/capture`)
 *  - Refunds (`POST /v1/refunds`)
 *  - Webhook signature verification (Stripe-Signature: t=…,v1=…)
 *
 * Stripe expects form-encoded request bodies, NOT JSON.
 */
final class StripeGateway implements PaymentGatewayInterface
{
    private const API_BASE = 'https://api.stripe.com';
    private const API_VERSION = '2024-06-20';
    private const SIGNATURE_TOLERANCE = 300; // 5 minutes

    /** @var array<string,mixed> */
    private array $cfg;
    private HttpClientInterface $http;

    /**
     * @param array<string,mixed>|null $cfg overrides config('payments.gateways.stripe')
     */
    public function __construct(?array $cfg = null, ?HttpClientInterface $http = null)
    {
        $this->cfg = $cfg ?? (array) (config('payments.gateways.stripe') ?? config('payments.stripe') ?? []);
        $this->http = $http ?? new Client();
    }

    public function name(): string
    {
        return 'stripe';
    }

    public function createCheckout(Invoice $invoice, array $options = []): array
    {
        $secret = (string) ($this->cfg['secret_key'] ?? '');
        if ($secret === '') {
            return ['success' => false, 'error' => 'Stripe is not configured'];
        }

        $currency = strtolower((string) ($options['currency'] ?? $invoice->currency ?? 'usd'));
        $amount = (float) ($options['amount'] ?? $invoice->total ?? 0);
        $unitAmount = (int) round($amount * 100);
        $description = (string) ($options['description'] ?? ('Invoice ' . ($invoice->number ?? $invoice->id ?? '')));
        $successUrl = (string) ($options['success_url'] ?? url('/account/invoices/' . ($invoice->id ?? '') . '?stripe=success'));
        $cancelUrl = (string) ($options['cancel_url'] ?? url('/account/invoices/' . ($invoice->id ?? '') . '?stripe=cancel'));
        $customerEmail = (string) ($options['customer_email'] ?? '');

        $params = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) ($invoice->id ?? ''),
            'metadata' => [
                'invoice_id' => (string) ($invoice->id ?? ''),
                'invoice_number' => (string) ($invoice->number ?? ''),
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $unitAmount,
                    'product_data' => ['name' => $description],
                ],
            ]],
        ];
        if ($customerEmail !== '') {
            $params['customer_email'] = $customerEmail;
        }

        $resp = $this->postForm('/v1/checkout/sessions', $params);
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json)) {
            return [
                'success' => false,
                'error' => $this->extractError($json) ?? ('Stripe HTTP ' . $resp->status),
            ];
        }
        return [
            'success' => true,
            'redirect_url' => (string) ($json['url'] ?? ''),
            'intent_id' => (string) ($json['id'] ?? ''),
        ];
    }

    public function capture(string $paymentReference, float $amount): array
    {
        $secret = (string) ($this->cfg['secret_key'] ?? '');
        if ($secret === '') {
            return ['success' => false, 'error' => 'Stripe is not configured'];
        }
        if ($paymentReference === '' || !str_starts_with($paymentReference, 'pi_')) {
            // Not a payment intent — assume already captured by Checkout Sessions.
            return ['success' => true, 'transaction_id' => $paymentReference];
        }
        $params = ['amount_to_capture' => (int) round($amount * 100)];
        $resp = $this->postForm('/v1/payment_intents/' . rawurlencode($paymentReference) . '/capture', $params);
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json)) {
            return ['success' => false, 'error' => $this->extractError($json) ?? ('Stripe HTTP ' . $resp->status)];
        }
        return ['success' => true, 'transaction_id' => (string) ($json['id'] ?? $paymentReference)];
    }

    public function refund(string $paymentReference, float $amount, ?string $reason = null): array
    {
        $secret = (string) ($this->cfg['secret_key'] ?? '');
        if ($secret === '') {
            return ['success' => false, 'error' => 'Stripe is not configured'];
        }
        $params = ['amount' => (int) round($amount * 100)];
        if (str_starts_with($paymentReference, 'pi_')) {
            $params['payment_intent'] = $paymentReference;
        } elseif (str_starts_with($paymentReference, 'ch_')) {
            $params['charge'] = $paymentReference;
        } else {
            // Fallback: try as payment_intent.
            $params['payment_intent'] = $paymentReference;
        }
        if ($reason !== null && in_array($reason, ['duplicate', 'fraudulent', 'requested_by_customer'], true)) {
            $params['reason'] = $reason;
        }

        $resp = $this->postForm('/v1/refunds', $params);
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json)) {
            return ['success' => false, 'error' => $this->extractError($json) ?? ('Stripe HTTP ' . $resp->status)];
        }
        return ['success' => true, 'refund_id' => (string) ($json['id'] ?? '')];
    }

    public function handleWebhook(string $rawBody, array $headers): array
    {
        $secret = (string) ($this->cfg['webhook_secret'] ?? '');
        $signatureHeader = (string) ($headers['stripe-signature'] ?? $headers['Stripe-Signature'] ?? '');
        if ($secret !== '') {
            if ($signatureHeader === '' || !$this->verifySignature($rawBody, $signatureHeader, $secret)) {
                return ['success' => false, 'error' => 'Invalid Stripe webhook signature'];
            }
        }
        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return ['success' => false, 'error' => 'Malformed Stripe webhook payload'];
        }
        return [
            'success' => true,
            'event' => (string) ($payload['type'] ?? 'unknown'),
            'payload' => $payload,
        ];
    }

    /**
     * Stripe-Signature: t=1492774577,v1=hex,v0=...
     *
     * @internal Exposed for direct unit testing.
     */
    public function verifySignature(string $payload, string $signatureHeader, string $secret, ?int $now = null): bool
    {
        $now ??= time();
        $timestamp = null;
        $v1Signatures = [];
        foreach (explode(',', $signatureHeader) as $part) {
            $part = trim($part);
            if ($part === '' || !str_contains($part, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $part, 2);
            if ($k === 't') {
                $timestamp = (int) $v;
            } elseif ($k === 'v1') {
                $v1Signatures[] = $v;
            }
        }
        if ($timestamp === null || $v1Signatures === []) {
            return false;
        }
        if (abs($now - $timestamp) > self::SIGNATURE_TOLERANCE) {
            return false;
        }
        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        foreach ($v1Signatures as $given) {
            if (hash_equals($expected, $given)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string,mixed> $params
     */
    private function postForm(string $path, array $params): Response
    {
        $secret = (string) ($this->cfg['secret_key'] ?? '');
        $url = self::API_BASE . $path;
        $headers = [
            'Authorization' => 'Bearer ' . $secret,
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Stripe-Version' => self::API_VERSION,
            'Accept' => 'application/json',
        ];
        return $this->http->request('POST', $url, $this->encodeForm($params), $headers);
    }

    /**
     * Encode nested arrays the way Stripe expects (`a[b][c]=v`).
     *
     * @param array<string,mixed> $data
     */
    public function encodeForm(array $data, string $prefix = ''): string
    {
        $pairs = [];
        foreach ($data as $key => $value) {
            $name = $prefix === '' ? (string) $key : $prefix . '[' . $key . ']';
            if (is_array($value)) {
                $pairs[] = $this->encodeForm($value, $name);
            } else {
                $pairs[] = rawurlencode($name) . '=' . rawurlencode((string) $value);
            }
        }
        return implode('&', array_filter($pairs, static fn ($p) => $p !== ''));
    }

    /**
     * @param mixed $json
     */
    private function extractError($json): ?string
    {
        if (!is_array($json) || !isset($json['error'])) {
            return null;
        }
        $err = $json['error'];
        if (is_string($err)) {
            return $err;
        }
        if (is_array($err)) {
            return (string) ($err['message'] ?? $err['code'] ?? 'Stripe error');
        }
        return null;
    }
}
