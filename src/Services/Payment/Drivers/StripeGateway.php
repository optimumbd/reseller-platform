<?php

declare(strict_types=1);

namespace App\Services\Payment\Drivers;

use App\Core\Http\Client;
use App\Models\Invoice;
use App\Services\Payment\Contracts\PaymentGatewayInterface;

final class StripeGateway implements PaymentGatewayInterface
{
    private array $cfg;
    private Client $http;

    public function __construct()
    {
        $this->cfg = (array) (config('payments.stripe') ?? []);
        $this->http = new Client();
    }

    public function name(): string { return 'stripe'; }

    public function createCheckout(Invoice $invoice, array $options = []): array
    {
        // In production: hit /v1/checkout/sessions. Stub returns a placeholder URL.
        if (empty($this->cfg['secret_key'])) {
            return ['success' => false, 'error' => 'Stripe not configured'];
        }
        $url = 'https://checkout.stripe.com/pay/' . bin2hex(random_bytes(8));
        return ['success' => true, 'redirect_url' => $url];
    }

    public function capture(string $paymentReference, float $amount): array
    {
        return ['success' => true, 'transaction_id' => $paymentReference];
    }

    public function refund(string $paymentReference, float $amount, ?string $reason = null): array
    {
        return ['success' => true, 'refund_id' => 're_' . bin2hex(random_bytes(8))];
    }

    public function handleWebhook(string $rawBody, array $headers): array
    {
        $signature = $headers['stripe-signature'] ?? '';
        $secret = (string) ($this->cfg['webhook_secret'] ?? '');
        if ($secret !== '' && $signature !== '') {
            // TODO: real Stripe signature verification.
        }
        $payload = json_decode($rawBody, true) ?: [];
        return ['success' => true, 'event' => $payload['type'] ?? 'unknown', 'payload' => $payload];
    }
}
