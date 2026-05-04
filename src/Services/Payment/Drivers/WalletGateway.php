<?php

declare(strict_types=1);

namespace App\Services\Payment\Drivers;

use App\Models\Invoice;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Payment\Contracts\PaymentGatewayInterface;

final class WalletGateway implements PaymentGatewayInterface
{
    public function name(): string { return 'wallet'; }

    public function createCheckout(Invoice $invoice, array $options = []): array
    {
        $wallet = Wallet::forUser((int) $invoice->user_id);
        $balance = (float) $wallet->balance;
        $needed = (float) $invoice->total - (float) $invoice->paid_amount;
        if ($balance + (float) $wallet->credit_limit < $needed) {
            return ['success' => false, 'error' => 'Insufficient wallet balance'];
        }
        $wallet->balance = $balance - $needed;
        $wallet->save();
        $tx = new WalletTransaction([
            'wallet_id' => (int) $wallet->id,
            'type' => 'debit',
            'amount' => $needed,
            'balance_after' => $wallet->balance,
            'description' => 'Invoice ' . $invoice->number,
            'reference_type' => 'invoice',
            'reference_id' => (int) $invoice->id,
        ]);
        $tx->save();
        $invoice->paid_amount = (float) $invoice->total;
        $invoice->status = 'paid';
        $invoice->paid_at = now();
        $invoice->save();
        return ['success' => true, 'redirect_url' => url('/account/invoices/' . $invoice->id)];
    }

    public function capture(string $paymentReference, float $amount): array
    {
        return ['success' => true, 'transaction_id' => $paymentReference];
    }

    public function refund(string $paymentReference, float $amount, ?string $reason = null): array
    {
        return ['success' => true, 'refund_id' => 'WALLET-' . bin2hex(random_bytes(4))];
    }

    public function handleWebhook(string $rawBody, array $headers): array
    {
        return ['success' => true, 'event' => 'noop'];
    }
}
