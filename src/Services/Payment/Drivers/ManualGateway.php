<?php

declare(strict_types=1);

namespace App\Services\Payment\Drivers;

use App\Models\Invoice;
use App\Services\Payment\Contracts\PaymentGatewayInterface;

final class ManualGateway implements PaymentGatewayInterface
{
    public function name(): string { return 'manual'; }

    public function createCheckout(Invoice $invoice, array $options = []): array
    {
        return [
            'success' => true,
            'redirect_url' => url('/account/invoices/' . $invoice->id),
        ];
    }

    public function capture(string $paymentReference, float $amount): array
    {
        return ['success' => true, 'transaction_id' => $paymentReference];
    }

    public function refund(string $paymentReference, float $amount, ?string $reason = null): array
    {
        return ['success' => true, 'refund_id' => 'MANUAL-' . bin2hex(random_bytes(4))];
    }

    public function handleWebhook(string $rawBody, array $headers): array
    {
        return ['success' => true, 'event' => 'noop'];
    }
}
