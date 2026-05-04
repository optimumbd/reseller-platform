<?php

declare(strict_types=1);

namespace App\Services\Payment\Contracts;

use App\Models\Invoice;

interface PaymentGatewayInterface
{
    public function name(): string;

    /** @return array{redirect_url?:string,intent_id?:string,token?:string,error?:string,success:bool} */
    public function createCheckout(Invoice $invoice, array $options = []): array;

    /** @return array{success:bool,transaction_id?:string,error?:string} */
    public function capture(string $paymentReference, float $amount): array;

    /** @return array{success:bool,refund_id?:string,error?:string} */
    public function refund(string $paymentReference, float $amount, ?string $reason = null): array;

    /** @return array{success:bool,event?:string,payload?:array,error?:string} */
    public function handleWebhook(string $rawBody, array $headers): array;
}
