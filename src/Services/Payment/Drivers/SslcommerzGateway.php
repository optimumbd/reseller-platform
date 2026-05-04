<?php

declare(strict_types=1);

namespace App\Services\Payment\Drivers;

use App\Core\Http\Client;
use App\Core\Http\HttpClientInterface;
use App\Core\Http\Response;
use App\Models\Invoice;
use App\Services\Payment\Contracts\PaymentGatewayInterface;

/**
 * SSLCommerz hosted-checkout driver.
 *
 *  - Init session: POST /gwprocess/v4/api.php  → JSON { GatewayPageURL, sessionkey }
 *  - IPN validate: GET  /validator/api/validationserverAPI.php?val_id=…
 *  - Refund: POST /validator/api/merchantTransIDvalidationAPI.php (initiate)
 *            then GET  /validator/api/refund.php?bank_tran_id=…&refund_amount=…
 *
 * The IPN POSTs to our webhook a form-encoded body containing val_id, status,
 * tran_id, amount, currency and a verify_sign. We re-validate against
 * SSLCommerz's validator endpoint to confirm the transaction is real.
 */
final class SslcommerzGateway implements PaymentGatewayInterface
{
    private const SANDBOX_BASE = 'https://sandbox.sslcommerz.com';
    private const PRODUCTION_BASE = 'https://securepay.sslcommerz.com';

    /** @var array<string,mixed> */
    private array $cfg;
    private HttpClientInterface $http;

    public function __construct(?array $cfg = null, ?HttpClientInterface $http = null)
    {
        $this->cfg = $cfg ?? (array) (config('payments.gateways.sslcommerz') ?? config('payments.sslcommerz') ?? []);
        $this->http = $http ?? new Client();
    }

    public function name(): string
    {
        return 'sslcommerz';
    }

    public function baseUrl(): string
    {
        return !empty($this->cfg['sandbox']) ? self::SANDBOX_BASE : self::PRODUCTION_BASE;
    }

    public function createCheckout(Invoice $invoice, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'SSLCommerz is not configured'];
        }
        $tranId = (string) ($options['tran_id'] ?? ('INV-' . ($invoice->id ?? '') . '-' . bin2hex(random_bytes(4))));
        $payload = [
            'store_id' => (string) $this->cfg['store_id'],
            'store_passwd' => (string) $this->cfg['store_password'],
            'total_amount' => number_format((float) ($options['amount'] ?? $invoice->total ?? 0), 2, '.', ''),
            'currency' => (string) ($options['currency'] ?? $invoice->currency ?? 'BDT'),
            'tran_id' => $tranId,
            'success_url' => (string) ($options['success_url'] ?? url('/webhooks/sslcommerz?result=success&tran_id=' . $tranId)),
            'fail_url' => (string) ($options['fail_url'] ?? url('/webhooks/sslcommerz?result=fail&tran_id=' . $tranId)),
            'cancel_url' => (string) ($options['cancel_url'] ?? url('/webhooks/sslcommerz?result=cancel&tran_id=' . $tranId)),
            'ipn_url' => (string) ($options['ipn_url'] ?? url('/webhooks/sslcommerz')),
            'cus_name' => (string) ($options['customer_name'] ?? 'Customer'),
            'cus_email' => (string) ($options['customer_email'] ?? 'no-reply@example.com'),
            'cus_phone' => (string) ($options['customer_phone'] ?? '01700000000'),
            'cus_add1' => (string) ($options['customer_address'] ?? 'N/A'),
            'cus_city' => (string) ($options['customer_city'] ?? 'Dhaka'),
            'cus_country' => (string) ($options['customer_country'] ?? 'Bangladesh'),
            'shipping_method' => 'NO',
            'product_name' => (string) ($options['description'] ?? ('Invoice ' . ($invoice->number ?? $invoice->id ?? ''))),
            'product_category' => (string) ($options['product_category'] ?? 'service'),
            'product_profile' => (string) ($options['product_profile'] ?? 'general'),
            'value_a' => (string) ($invoice->id ?? ''),
            'value_b' => (string) ($invoice->number ?? ''),
        ];
        $resp = $this->http->request(
            'POST',
            $this->baseUrl() . '/gwprocess/v4/api.php',
            http_build_query($payload),
            ['Content-Type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json'],
        );
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json) || ($json['status'] ?? '') !== 'SUCCESS') {
            $err = is_array($json) ? (string) ($json['failedreason'] ?? $json['status'] ?? 'SSLCommerz init failed') : 'SSLCommerz init failed';
            return ['success' => false, 'error' => $err];
        }
        return [
            'success' => true,
            'redirect_url' => (string) ($json['GatewayPageURL'] ?? ''),
            'token' => (string) ($json['sessionkey'] ?? ''),
            'intent_id' => $tranId,
        ];
    }

    public function capture(string $paymentReference, float $amount): array
    {
        // SSLCommerz auto-captures via hosted checkout. Trust the IPN value or
        // re-verify by querying the validator endpoint when val_id is known.
        if (str_starts_with($paymentReference, 'val:')) {
            return $this->validate(substr($paymentReference, 4));
        }
        return ['success' => true, 'transaction_id' => $paymentReference];
    }

    public function refund(string $paymentReference, float $amount, ?string $reason = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'SSLCommerz is not configured'];
        }
        // SSLCommerz refunds need bank_tran_id (the bank transaction id), not tran_id.
        // We expect the caller to pass `bank_tran_id` via $paymentReference.
        $params = [
            'bank_tran_id' => $paymentReference,
            'refund_amount' => number_format($amount, 2, '.', ''),
            'refund_remarks' => $reason ?? 'Refund requested by merchant',
            'refe_id' => 'REF-' . bin2hex(random_bytes(4)),
            'store_id' => (string) $this->cfg['store_id'],
            'store_passwd' => (string) $this->cfg['store_password'],
            'format' => 'json',
        ];
        $resp = $this->http->get(
            $this->baseUrl() . '/validator/api/merchantTransIDvalidationAPI.php',
            ['Accept' => 'application/json'],
            $params,
        );
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json)) {
            return ['success' => false, 'error' => 'SSLCommerz refund failed'];
        }
        $status = strtoupper((string) ($json['APIConnect'] ?? $json['status'] ?? ''));
        if (!in_array($status, ['DONE', 'SUCCESS', 'INITIATED'], true)) {
            return ['success' => false, 'error' => (string) ($json['errorReason'] ?? 'Refund rejected')];
        }
        return ['success' => true, 'refund_id' => (string) ($json['refund_ref_id'] ?? $params['refe_id'])];
    }

    /**
     * SSLCommerz IPN posts a form-encoded body. We treat status=VALID/VALIDATED
     * as a candidate, then re-validate against the validator API to be certain.
     */
    public function handleWebhook(string $rawBody, array $headers): array
    {
        $payload = [];
        if ($rawBody !== '') {
            parse_str($rawBody, $payload);
        }
        $valId = (string) ($payload['val_id'] ?? '');
        $status = strtoupper((string) ($payload['status'] ?? ''));
        if ($valId === '') {
            return ['success' => false, 'error' => 'Missing val_id'];
        }
        if (!in_array($status, ['VALID', 'VALIDATED'], true)) {
            return ['success' => true, 'event' => 'payment.' . strtolower($status ?: 'unknown'), 'payload' => $payload];
        }
        $verified = $this->validate($valId);
        return [
            'success' => (bool) ($verified['success'] ?? false),
            'event' => 'payment.completed',
            'payload' => array_merge($payload, $verified),
        ];
    }

    /**
     * Re-query SSLCommerz to confirm a transaction is valid. Returns
     * ['success' => true, 'transaction_id' => '…', 'amount' => float] on success.
     *
     * @return array<string,mixed>
     */
    public function validate(string $valId): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'SSLCommerz is not configured'];
        }
        $params = [
            'val_id' => $valId,
            'store_id' => (string) $this->cfg['store_id'],
            'store_passwd' => (string) $this->cfg['store_password'],
            'v' => 1,
            'format' => 'json',
        ];
        $resp = $this->http->get(
            $this->baseUrl() . '/validator/api/validationserverAPI.php',
            ['Accept' => 'application/json'],
            $params,
        );
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json)) {
            return ['success' => false, 'error' => 'SSLCommerz validation failed'];
        }
        $status = strtoupper((string) ($json['status'] ?? ''));
        if (!in_array($status, ['VALID', 'VALIDATED'], true)) {
            return ['success' => false, 'error' => 'Status ' . $status, 'raw' => $json];
        }
        return [
            'success' => true,
            'transaction_id' => (string) ($json['tran_id'] ?? $valId),
            'bank_tran_id' => (string) ($json['bank_tran_id'] ?? ''),
            'amount' => (float) ($json['amount'] ?? 0),
            'currency' => (string) ($json['currency'] ?? 'BDT'),
        ];
    }

    public function isConfigured(): bool
    {
        return !empty($this->cfg['store_id']) && !empty($this->cfg['store_password']);
    }
}
