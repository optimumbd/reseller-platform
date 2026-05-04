<?php

declare(strict_types=1);

namespace App\Services\Payment\Drivers;

use App\Core\Http\Client;
use App\Core\Http\HttpClientInterface;
use App\Core\Http\Response;
use App\Models\Invoice;
use App\Services\Payment\Contracts\PaymentGatewayInterface;

/**
 * bKash Tokenized Checkout (v1.2.0-beta) driver.
 *
 * Flow:
 *   1. Grant a session id_token from app_key/app_secret + username/password.
 *   2. Create a payment for the invoice → bkashURL the customer is redirected to.
 *   3. After approval bKash redirects to callbackURL with paymentID + status.
 *   4. We execute the payment (or query status) to confirm.
 *   5. Refund through /payment/refund.
 *
 * bKash does not push webhooks; handleWebhook here interprets the redirect
 * callback by querying the payment status server-side.
 */
final class BkashGateway implements PaymentGatewayInterface
{
    private const SANDBOX_BASE = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';
    private const PRODUCTION_BASE = 'https://tokenized.pay.bka.sh/v1.2.0-beta';

    /** @var array<string,mixed> */
    private array $cfg;
    private HttpClientInterface $http;
    private ?string $cachedToken = null;

    public function __construct(?array $cfg = null, ?HttpClientInterface $http = null)
    {
        $this->cfg = $cfg ?? (array) (config('payments.gateways.bkash') ?? config('payments.bkash') ?? []);
        $this->http = $http ?? new Client();
    }

    public function name(): string
    {
        return 'bkash';
    }

    public function baseUrl(): string
    {
        return !empty($this->cfg['sandbox']) ? self::SANDBOX_BASE : self::PRODUCTION_BASE;
    }

    public function createCheckout(Invoice $invoice, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'bKash is not configured'];
        }
        $token = $this->grantToken();
        if ($token === null) {
            return ['success' => false, 'error' => 'Could not obtain bKash session token'];
        }
        $amount = number_format((float) ($options['amount'] ?? $invoice->total ?? 0), 2, '.', '');
        $body = [
            'mode' => '0011',
            'payerReference' => (string) ($options['payer_reference'] ?? $invoice->user_id ?? '01000000000'),
            'callbackURL' => (string) ($options['callback_url'] ?? url('/webhooks/bkash')),
            'amount' => $amount,
            'currency' => (string) ($options['currency'] ?? 'BDT'),
            'intent' => (string) ($options['intent'] ?? 'sale'),
            'merchantInvoiceNumber' => (string) ($invoice->number ?? $invoice->id ?? ''),
        ];
        $resp = $this->post('/tokenized/checkout/create', $body, $token);
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json)) {
            return ['success' => false, 'error' => $this->extractError($json) ?? ('bKash HTTP ' . $resp->status)];
        }
        if (($json['statusCode'] ?? null) !== '0000') {
            return ['success' => false, 'error' => (string) ($json['statusMessage'] ?? 'bKash create failed')];
        }
        return [
            'success' => true,
            'redirect_url' => (string) ($json['bkashURL'] ?? ''),
            'intent_id' => (string) ($json['paymentID'] ?? ''),
            'token' => (string) ($json['paymentID'] ?? ''),
        ];
    }

    public function capture(string $paymentReference, float $amount): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'bKash is not configured'];
        }
        $token = $this->grantToken();
        if ($token === null) {
            return ['success' => false, 'error' => 'Could not obtain bKash session token'];
        }
        $resp = $this->post('/tokenized/checkout/execute', ['paymentID' => $paymentReference], $token);
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json)) {
            return ['success' => false, 'error' => $this->extractError($json) ?? ('bKash HTTP ' . $resp->status)];
        }
        if (($json['statusCode'] ?? null) !== '0000' && ($json['transactionStatus'] ?? null) !== 'Completed') {
            return ['success' => false, 'error' => (string) ($json['statusMessage'] ?? 'bKash execute failed')];
        }
        return [
            'success' => true,
            'transaction_id' => (string) ($json['trxID'] ?? $paymentReference),
        ];
    }

    public function refund(string $paymentReference, float $amount, ?string $reason = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'bKash is not configured'];
        }
        $token = $this->grantToken();
        if ($token === null) {
            return ['success' => false, 'error' => 'Could not obtain bKash session token'];
        }
        $body = [
            'paymentID' => $paymentReference,
            'amount' => number_format($amount, 2, '.', ''),
            'trxID' => (string) ($reason ?? ''),
            'sku' => 'refund',
            'reason' => $reason ?? 'requested by merchant',
        ];
        $resp = $this->post('/tokenized/checkout/payment/refund', $body, $token);
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json)) {
            return ['success' => false, 'error' => $this->extractError($json) ?? ('bKash HTTP ' . $resp->status)];
        }
        if (($json['statusCode'] ?? null) !== '0000') {
            return ['success' => false, 'error' => (string) ($json['statusMessage'] ?? 'bKash refund failed')];
        }
        return ['success' => true, 'refund_id' => (string) ($json['refundTrxID'] ?? '')];
    }

    /**
     * bKash does not push webhooks — callbackURL receives a GET with paymentID & status.
     * We accept either a query-string body (`paymentID=...&status=success`) or JSON.
     */
    public function handleWebhook(string $rawBody, array $headers): array
    {
        $payload = [];
        if ($rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            } else {
                parse_str($rawBody, $payload);
            }
        }
        $paymentId = (string) ($payload['paymentID'] ?? '');
        $status = strtolower((string) ($payload['status'] ?? ''));
        if ($paymentId === '') {
            return ['success' => false, 'error' => 'Missing paymentID'];
        }
        if ($status !== '' && $status !== 'success') {
            return ['success' => true, 'event' => 'payment.' . $status, 'payload' => $payload];
        }

        // Verify by querying bKash for the canonical status.
        if (!$this->isConfigured()) {
            return ['success' => true, 'event' => 'payment.callback', 'payload' => $payload];
        }
        $token = $this->grantToken();
        if ($token === null) {
            return ['success' => false, 'error' => 'Could not obtain bKash session token'];
        }
        $resp = $this->post('/tokenized/checkout/payment/status', ['paymentID' => $paymentId], $token);
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json)) {
            return ['success' => false, 'error' => $this->extractError($json) ?? ('bKash HTTP ' . $resp->status)];
        }
        $verified = ($json['transactionStatus'] ?? null) === 'Completed';
        return [
            'success' => $verified,
            'event' => $verified ? 'payment.completed' : 'payment.' . strtolower((string) ($json['transactionStatus'] ?? 'unknown')),
            'payload' => $json,
        ];
    }

    public function isConfigured(): bool
    {
        return !empty($this->cfg['app_key'])
            && !empty($this->cfg['app_secret'])
            && !empty($this->cfg['username'])
            && !empty($this->cfg['password']);
    }

    /**
     * Issue or reuse an id_token via /token/grant.
     */
    public function grantToken(): ?string
    {
        if ($this->cachedToken !== null) {
            return $this->cachedToken;
        }
        if (!$this->isConfigured()) {
            return null;
        }
        $headers = [
            'username' => (string) $this->cfg['username'],
            'password' => (string) $this->cfg['password'],
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        $body = [
            'app_key' => (string) $this->cfg['app_key'],
            'app_secret' => (string) $this->cfg['app_secret'],
        ];
        $resp = $this->http->request('POST', $this->baseUrl() . '/tokenized/checkout/token/grant', $body, $headers);
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json) || empty($json['id_token'])) {
            return null;
        }
        $this->cachedToken = (string) $json['id_token'];
        return $this->cachedToken;
    }

    /**
     * @param array<string,mixed> $body
     */
    private function post(string $path, array $body, string $token): Response
    {
        $headers = [
            'Authorization' => $token,
            'X-APP-Key' => (string) ($this->cfg['app_key'] ?? ''),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        return $this->http->request('POST', $this->baseUrl() . $path, $body, $headers);
    }

    /**
     * @param mixed $json
     */
    private function extractError($json): ?string
    {
        if (!is_array($json)) {
            return null;
        }
        if (isset($json['statusMessage'])) {
            return (string) $json['statusMessage'];
        }
        if (isset($json['errorMessage'])) {
            return (string) $json['errorMessage'];
        }
        return null;
    }
}
