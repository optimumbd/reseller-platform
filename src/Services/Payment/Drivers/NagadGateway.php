<?php

declare(strict_types=1);

namespace App\Services\Payment\Drivers;

use App\Models\Invoice;
use App\Services\Payment\Contracts\PaymentGatewayInterface;

final class NagadGateway implements PaymentGatewayInterface
{
    public function name(): string { return 'nagad'; }
    public function createCheckout(Invoice $invoice, array $options = []): array { return ['success' => true, 'redirect_url' => '/checkout/nagad']; }
    public function capture(string $paymentReference, float $amount): array { return ['success' => true, 'transaction_id' => $paymentReference]; }
    public function refund(string $paymentReference, float $amount, ?string $reason = null): array { return ['success' => true]; }
    public function handleWebhook(string $rawBody, array $headers): array { return ['success' => true, 'event' => 'noop']; }
}
