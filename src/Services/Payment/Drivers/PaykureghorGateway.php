<?php

declare(strict_types=1);

namespace App\Services\Payment\Drivers;

use App\Core\Http\Client;
use App\Core\Http\HttpClientInterface;
use App\Core\Http\Response;
use App\Models\Invoice;
use App\Services\Payment\Contracts\PaymentGatewayInterface;

/**
 * Pay KureGhor hosted-checkout driver.
 *
 * Docs: https://paykureghor.com/developers/docs
 *
 * Endpoints (live):
 *   POST https://checkout.paykureghor.com/api/payment/create
 *   POST https://checkout.paykureghor.com/api/payment/verify
 *
 * Auth headers (every request):
 *   API-KEY    : merchant app key
 *   SECRET-KEY : merchant secret
 *   BRAND-KEY  : brand identifier from "Brands" tab
 *
 * Flow:
 *   1. POST /payment/create with amount + success_url + cancel_url + customer
 *      details → response carries `payment_url`. We redirect the customer there.
 *   2. After paying the customer is bounced to success_url or cancel_url with
 *      query parameters: transactionId, paymentMethod, paymentAmount,
 *      paymentFee, status (pending|success|failed).
 *   3. The merchant is expected to re-verify the transaction server-side via
 *      POST /payment/verify {transaction_id} before honouring the order.
 *
 * Pay KureGhor does NOT push asynchronous webhooks; handleWebhook here parses
 * the success_url/cancel_url callback (query string or body) and re-verifies
 * by calling the verify endpoint.
 */
class PaykureghorGateway implements PaymentGatewayInterface
{
    protected const PRODUCTION_BASE = 'https://checkout.paykureghor.com';

    /** @var array<string,mixed> */
    protected array $cfg;
    protected HttpClientInterface $http;

    /**
     * @param array<string,mixed>|null $cfg overrides config('payments.gateways.<name>')
     */
    public function __construct(?array $cfg = null, ?HttpClientInterface $http = null)
    {
        $key = $this->name();
        $this->cfg = $cfg ?? (array) (config('payments.gateways.' . $key) ?? config('payments.' . $key) ?? []);
        $this->http = $http ?? new Client();
    }

    public function name(): string
    {
        return 'paykureghor';
    }

    /**
     * Human-readable brand label, used in error messages. Subclasses
     * override to rebrand the same driver under a different name.
     */
    protected function brandName(): string
    {
        return 'Pay KureGhor';
    }

    public function baseUrl(): string
    {
        $override = trim((string) ($this->cfg['base_url'] ?? ''));
        if ($override !== '') {
            return rtrim($override, '/');
        }
        return static::PRODUCTION_BASE;
    }

    public function createCheckout(Invoice $invoice, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => $this->brandName() . ' is not configured'];
        }
        $amount = (float) ($options['amount'] ?? $invoice->total ?? 0);
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Invalid amount'];
        }
        $payload = [
            'cus_name' => (string) ($options['customer_name'] ?? 'Customer'),
            'cus_email' => (string) ($options['customer_email'] ?? 'no-reply@example.com'),
            'amount' => $this->formatAmount($amount),
            'success_url' => (string) ($options['success_url']
                ?? url('/webhooks/' . $this->name() . '?result=success&invoice=' . ($invoice->id ?? ''))),
            'cancel_url' => (string) ($options['cancel_url']
                ?? url('/webhooks/' . $this->name() . '?result=cancel&invoice=' . ($invoice->id ?? ''))),
        ];

        $metadata = $options['metadata'] ?? [
            'invoice_id' => $invoice->id ?? null,
            'invoice_number' => $invoice->number ?? null,
        ];
        if (is_array($metadata) && $metadata !== []) {
            $payload['metadata'] = $metadata;
        }

        $resp = $this->http->post(
            $this->baseUrl() . '/api/payment/create',
            $payload,
            $this->headers(),
        );
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json)) {
            return ['success' => false, 'error' => $this->extractError($json) ?? ($this->brandName() . ' HTTP ' . $resp->status)];
        }
        if (!($json['status'] ?? false)) {
            return ['success' => false, 'error' => (string) ($json['message'] ?? ($this->brandName() . ' create failed'))];
        }
        $paymentUrl = (string) ($json['payment_url'] ?? '');
        if ($paymentUrl === '') {
            return ['success' => false, 'error' => $this->brandName() . ' did not return a payment_url'];
        }
        return [
            'success' => true,
            'redirect_url' => $paymentUrl,
            'token' => $this->extractTokenFromUrl($paymentUrl),
            'intent_id' => (string) ($invoice->number ?? $invoice->id ?? ''),
        ];
    }

    /**
     * Pay KureGhor uses synchronous redirect-based capture: the gateway
     * already collected the funds before bouncing the customer back. We
     * authoritatively verify by hitting the verify endpoint.
     */
    public function capture(string $paymentReference, float $amount): array
    {
        $verified = $this->verify($paymentReference);
        if (!($verified['success'] ?? false)) {
            return ['success' => false, 'error' => (string) ($verified['error'] ?? ($this->brandName() . ' verify failed'))];
        }
        if ($amount > 0) {
            $verifiedAmount = (float) ($verified['amount'] ?? 0);
            if ($verifiedAmount > 0 && abs($verifiedAmount - $amount) > 0.01) {
                return ['success' => false, 'error' => 'Amount mismatch (expected ' . $amount . ', got ' . $verifiedAmount . ')'];
            }
        }
        return ['success' => true, 'transaction_id' => (string) ($verified['transaction_id'] ?? $paymentReference)];
    }

    /**
     * Pay KureGhor's documentation does not expose a refund endpoint; refunds
     * have to be processed manually from the merchant dashboard. We fail
     * loudly so callers do not silently treat the refund as completed.
     */
    public function refund(string $paymentReference, float $amount, ?string $reason = null): array
    {
        return [
            'success' => false,
            'error' => $this->brandName() . ' refunds are not exposed via API; please refund from the merchant dashboard.',
        ];
    }

    /**
     * Parse the success_url / cancel_url callback Pay KureGhor sends the
     * customer back with, then re-verify against the verify endpoint.
     *
     * Accepts either a raw query string ("transactionId=…&status=success&…")
     * or a JSON body with the same keys.
     *
     * @param array<string,mixed> $headers
     * @return array<string,mixed>
     */
    public function handleWebhook(string $rawBody, array $headers): array
    {
        $payload = $this->decodeWebhookBody($rawBody);
        $txId = (string) ($payload['transactionId'] ?? $payload['transaction_id'] ?? '');
        if ($txId === '') {
            return ['success' => false, 'error' => 'Missing transactionId'];
        }
        $status = strtolower((string) ($payload['status'] ?? ''));
        if (!in_array($status, ['success', 'completed'], true)) {
            return [
                'success' => true,
                'event' => 'payment.' . ($status !== '' ? $status : 'unknown'),
                'payload' => $payload,
            ];
        }
        $verified = $this->verify($txId);
        return [
            'success' => (bool) ($verified['success'] ?? false),
            'event' => 'payment.completed',
            'payload' => array_merge($payload, $verified),
        ];
    }

    /**
     * Re-query Pay KureGhor for the authoritative state of a transaction.
     *
     * @return array<string,mixed>
     */
    public function verify(string $transactionId): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => $this->brandName() . ' is not configured'];
        }
        if ($transactionId === '') {
            return ['success' => false, 'error' => 'transaction_id is required'];
        }
        $resp = $this->http->post(
            $this->baseUrl() . '/api/payment/verify',
            ['transaction_id' => $transactionId],
            $this->headers(),
        );
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json)) {
            return ['success' => false, 'error' => $this->extractError($json) ?? ($this->brandName() . ' HTTP ' . $resp->status)];
        }
        $status = strtoupper((string) ($json['status'] ?? ''));
        if (!in_array($status, ['COMPLETED', 'SUCCESS'], true)) {
            return [
                'success' => false,
                'error' => 'Status ' . ($status !== '' ? $status : 'UNKNOWN'),
                'raw' => $json,
            ];
        }
        return [
            'success' => true,
            'transaction_id' => (string) ($json['transaction_id'] ?? $transactionId),
            'amount' => (float) ($json['amount'] ?? 0),
            'payment_method' => (string) ($json['payment_method'] ?? ''),
            'cus_name' => (string) ($json['cus_name'] ?? ''),
            'cus_email' => (string) ($json['cus_email'] ?? ''),
            'metadata' => is_array($json['metadata'] ?? null) ? $json['metadata'] : [],
        ];
    }

    public function isConfigured(): bool
    {
        return !empty($this->cfg['api_key'])
            && !empty($this->cfg['secret_key'])
            && !empty($this->cfg['brand_key']);
    }

    /**
     * @return array<string,string>
     */
    private function headers(): array
    {
        return [
            'API-KEY' => (string) $this->cfg['api_key'],
            'SECRET-KEY' => (string) $this->cfg['secret_key'],
            'BRAND-KEY' => (string) $this->cfg['brand_key'],
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeWebhookBody(string $rawBody): array
    {
        if ($rawBody === '') {
            return [];
        }
        $trimmed = ltrim($rawBody);
        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        $parsed = [];
        parse_str($rawBody, $parsed);
        return $parsed;
    }

    /**
     * Pay KureGhor's `payment_url` is opaque from our side. We extract a
     * trailing identifier (last path component or `?token=…` query value)
     * so the platform can store something meaningful as the gateway token.
     */
    private function extractTokenFromUrl(string $paymentUrl): string
    {
        $parts = parse_url($paymentUrl);
        if (!is_array($parts)) {
            return '';
        }
        if (!empty($parts['query'])) {
            $q = [];
            parse_str((string) $parts['query'], $q);
            foreach (['token', 'paymentId', 'payment_id', 'id'] as $k) {
                if (!empty($q[$k])) {
                    return (string) $q[$k];
                }
            }
        }
        if (!empty($parts['path'])) {
            $segments = array_values(array_filter(explode('/', (string) $parts['path'])));
            if ($segments !== []) {
                return (string) end($segments);
            }
        }
        return '';
    }

    /**
     * Format the amount per Pay KureGhor's example: "10", "10.50", "10.6".
     * No trailing zeros for natural numbers.
     */
    private function formatAmount(float $amount): string
    {
        if ($amount == (float) (int) $amount) {
            return (string) (int) $amount;
        }
        return rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');
    }

    /**
     * @param mixed $json
     */
    private function extractError($json): ?string
    {
        if (!is_array($json)) {
            return null;
        }
        foreach (['message', 'error', 'detail'] as $k) {
            if (!empty($json[$k]) && is_string($json[$k])) {
                return $json[$k];
            }
        }
        if (isset($json['errors'])) {
            $errors = $json['errors'];
            if (is_array($errors)) {
                $first = reset($errors);
                if (is_string($first)) {
                    return $first;
                }
                if (is_array($first) && isset($first['message']) && is_string($first['message'])) {
                    return $first['message'];
                }
            } elseif (is_string($errors)) {
                return $errors;
            }
        }
        return null;
    }
}
