<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Payment;

use App\Core\Http\Response;
use App\Models\Invoice;
use App\Services\Payment\Drivers\BkashGateway;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryHttpClient;

final class BkashGatewayTest extends TestCase
{
    public function test_name_is_bkash(): void
    {
        $g = new BkashGateway([]);
        $this->assertSame('bkash', $g->name());
    }

    public function test_unconfigured_create_returns_error(): void
    {
        $g = new BkashGateway([], new InMemoryHttpClient());
        $invoice = $this->makeInvoice(1, 'INV-1', 100.0);
        $result = $g->createCheckout($invoice);
        $this->assertFalse($result['success']);
        $this->assertSame('bKash is not configured', $result['error']);
    }

    public function test_base_url_switches_on_sandbox_flag(): void
    {
        $live = new BkashGateway($this->cfg(['sandbox' => false]));
        $sandbox = new BkashGateway($this->cfg(['sandbox' => true]));

        $this->assertStringStartsWith('https://tokenized.pay.bka.sh/', $live->baseUrl());
        $this->assertStringStartsWith('https://tokenized.sandbox.bka.sh/', $sandbox->baseUrl());
    }

    public function test_grant_token_posts_to_token_grant(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'id_token' => 'tok-abc',
            'expires_in' => 3600,
        ]));
        $g = new BkashGateway($this->cfg(), $http);

        $token = $g->grantToken();
        $this->assertSame('tok-abc', $token);

        $req = $http->lastRequest();
        $this->assertSame('POST', $req['method']);
        $this->assertStringContainsString('/tokenized/checkout/token/grant', $req['url']);
        $this->assertSame('user', $req['headers']['username']);
        $this->assertSame('pass', $req['headers']['password']);
        $this->assertSame('app-key', $req['body']['app_key']);
        $this->assertSame('app-secret', $req['body']['app_secret']);
    }

    public function test_grant_token_caches_id_token(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, ['id_token' => 'tok-once']));
        $g = new BkashGateway($this->cfg(), $http);

        $g->grantToken();
        $g->grantToken();
        $g->grantToken();

        $this->assertCount(1, $http->requests, 'token grant must be issued only once');
    }

    public function test_create_checkout_then_create_payment(): void
    {
        $http = new InMemoryHttpClient([
            InMemoryHttpClient::jsonResponse(200, ['id_token' => 'tok']),
            InMemoryHttpClient::jsonResponse(200, [
                'statusCode' => '0000',
                'paymentID' => 'PAY1',
                'bkashURL' => 'https://sandbox/redirect/PAY1',
            ]),
        ]);
        $g = new BkashGateway($this->cfg(), $http);
        $invoice = $this->makeInvoice(7, 'INV-7', 250.5);

        $r = $g->createCheckout($invoice, ['callback_url' => 'https://shop/test/callback']);

        $this->assertTrue($r['success']);
        $this->assertSame('PAY1', $r['intent_id']);
        $this->assertSame('https://sandbox/redirect/PAY1', $r['redirect_url']);

        $createReq = $http->requests[1];
        $this->assertStringContainsString('/tokenized/checkout/create', $createReq['url']);
        $this->assertSame('tok', $createReq['headers']['Authorization']);
        $this->assertSame('app-key', $createReq['headers']['X-APP-Key']);
        $this->assertSame('250.50', $createReq['body']['amount']);
        $this->assertSame('BDT', $createReq['body']['currency']);
        $this->assertSame('https://shop/test/callback', $createReq['body']['callbackURL']);
    }

    public function test_create_checkout_surfaces_status_message(): void
    {
        $http = new InMemoryHttpClient([
            InMemoryHttpClient::jsonResponse(200, ['id_token' => 'tok']),
            InMemoryHttpClient::jsonResponse(200, ['statusCode' => '2001', 'statusMessage' => 'Insufficient balance']),
        ]);
        $g = new BkashGateway($this->cfg(), $http);
        $r = $g->createCheckout($this->makeInvoice(1, 'INV-1', 100.0));
        $this->assertFalse($r['success']);
        $this->assertSame('Insufficient balance', $r['error']);
    }

    public function test_capture_executes_payment(): void
    {
        $http = new InMemoryHttpClient([
            InMemoryHttpClient::jsonResponse(200, ['id_token' => 'tok']),
            InMemoryHttpClient::jsonResponse(200, [
                'statusCode' => '0000',
                'transactionStatus' => 'Completed',
                'trxID' => 'TRX-XYZ',
            ]),
        ]);
        $g = new BkashGateway($this->cfg(), $http);
        $r = $g->capture('PAY1', 100.0);

        $this->assertTrue($r['success']);
        $this->assertSame('TRX-XYZ', $r['transaction_id']);
        $this->assertStringContainsString('/tokenized/checkout/execute', $http->requests[1]['url']);
        $this->assertSame('PAY1', $http->requests[1]['body']['paymentID']);
    }

    public function test_handle_webhook_verifies_callback_via_status_query(): void
    {
        $http = new InMemoryHttpClient([
            InMemoryHttpClient::jsonResponse(200, ['id_token' => 'tok']),
            InMemoryHttpClient::jsonResponse(200, [
                'transactionStatus' => 'Completed',
                'trxID' => 'TRX-1',
                'paymentID' => 'PAY1',
            ]),
        ]);
        $g = new BkashGateway($this->cfg(), $http);

        $body = http_build_query(['paymentID' => 'PAY1', 'status' => 'success']);
        $r = $g->handleWebhook($body, []);

        $this->assertTrue($r['success']);
        $this->assertSame('payment.completed', $r['event']);
        $this->assertStringContainsString('/tokenized/checkout/payment/status', $http->requests[1]['url']);
    }

    public function test_handle_webhook_short_circuits_for_failed_callback(): void
    {
        $g = new BkashGateway($this->cfg(), new InMemoryHttpClient());
        $body = http_build_query(['paymentID' => 'PAY1', 'status' => 'failure']);
        $r = $g->handleWebhook($body, []);

        $this->assertTrue($r['success']);
        $this->assertSame('payment.failure', $r['event']);
    }

    public function test_refund_calls_refund_endpoint(): void
    {
        $http = new InMemoryHttpClient([
            InMemoryHttpClient::jsonResponse(200, ['id_token' => 'tok']),
            InMemoryHttpClient::jsonResponse(200, ['statusCode' => '0000', 'refundTrxID' => 'RFD-1']),
        ]);
        $g = new BkashGateway($this->cfg(), $http);

        $r = $g->refund('PAY1', 50.0, 'TRX-XYZ');
        $this->assertTrue($r['success']);
        $this->assertSame('RFD-1', $r['refund_id']);
        $this->assertStringContainsString('/tokenized/checkout/payment/refund', $http->requests[1]['url']);
        $this->assertSame('50.00', $http->requests[1]['body']['amount']);
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private function cfg(array $overrides = []): array
    {
        return array_merge([
            'app_key' => 'app-key',
            'app_secret' => 'app-secret',
            'username' => 'user',
            'password' => 'pass',
            'sandbox' => true,
        ], $overrides);
    }

    private function makeInvoice(int $id, string $number, float $total): Invoice
    {
        $i = new Invoice();
        $i->id = $id;
        $i->number = $number;
        $i->total = $total;
        $i->user_id = 99;
        return $i;
    }
}
