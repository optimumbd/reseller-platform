<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Payment;

use App\Models\Invoice;
use App\Services\Payment\Drivers\SslcommerzGateway;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryHttpClient;

final class SslcommerzGatewayTest extends TestCase
{
    public function test_name_is_sslcommerz(): void
    {
        $g = new SslcommerzGateway([]);
        $this->assertSame('sslcommerz', $g->name());
    }

    public function test_unconfigured_gateway_returns_error(): void
    {
        $g = new SslcommerzGateway([], new InMemoryHttpClient());
        $r = $g->createCheckout($this->makeInvoice(1, 'INV-1', 100.0));
        $this->assertFalse($r['success']);
        $this->assertSame('SSLCommerz is not configured', $r['error']);
    }

    public function test_base_url_switches_on_sandbox_flag(): void
    {
        $live = new SslcommerzGateway($this->cfg(['sandbox' => false]));
        $sandbox = new SslcommerzGateway($this->cfg(['sandbox' => true]));
        $this->assertStringStartsWith('https://securepay.sslcommerz.com', $live->baseUrl());
        $this->assertStringStartsWith('https://sandbox.sslcommerz.com', $sandbox->baseUrl());
    }

    public function test_create_checkout_posts_form_encoded_session_init(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'status' => 'SUCCESS',
            'sessionkey' => 'SESS-1',
            'GatewayPageURL' => 'https://sandbox/redirect/SESS-1',
        ]));
        $g = new SslcommerzGateway($this->cfg(), $http);

        $r = $g->createCheckout(
            $this->makeInvoice(7, 'INV-7', 1234.50),
            ['customer_email' => 'c@d.com', 'tran_id' => 'TRAN-7'],
        );
        $this->assertTrue($r['success']);
        $this->assertSame('https://sandbox/redirect/SESS-1', $r['redirect_url']);
        $this->assertSame('SESS-1', $r['token']);
        $this->assertSame('TRAN-7', $r['intent_id']);

        $req = $http->lastRequest();
        $this->assertSame('POST', $req['method']);
        $this->assertStringContainsString('/gwprocess/v4/api.php', $req['url']);
        $this->assertSame('application/x-www-form-urlencoded', $req['headers']['Content-Type']);
        parse_str($req['body'], $form);
        $this->assertSame('store-id', $form['store_id']);
        $this->assertSame('store-pass', $form['store_passwd']);
        $this->assertSame('1234.50', $form['total_amount']);
        $this->assertSame('TRAN-7', $form['tran_id']);
        $this->assertSame('c@d.com', $form['cus_email']);
    }

    public function test_create_checkout_returns_error_on_failure_response(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'status' => 'FAILED',
            'failedreason' => 'Invalid store',
        ]));
        $g = new SslcommerzGateway($this->cfg(), $http);
        $r = $g->createCheckout($this->makeInvoice(1, 'INV-1', 100.0));
        $this->assertFalse($r['success']);
        $this->assertSame('Invalid store', $r['error']);
    }

    public function test_validate_calls_validation_endpoint(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'status' => 'VALID',
            'tran_id' => 'TRAN-1',
            'bank_tran_id' => 'BANK-1',
            'amount' => '100.00',
            'currency' => 'BDT',
        ]));
        $g = new SslcommerzGateway($this->cfg(), $http);

        $r = $g->validate('VAL-1');
        $this->assertTrue($r['success']);
        $this->assertSame('TRAN-1', $r['transaction_id']);
        $this->assertSame('BANK-1', $r['bank_tran_id']);
        $this->assertSame(100.0, $r['amount']);

        $req = $http->lastRequest();
        $this->assertStringContainsString('/validator/api/validationserverAPI.php', $req['url']);
        $this->assertStringContainsString('val_id=VAL-1', $req['url']);
        $this->assertStringContainsString('store_id=store-id', $req['url']);
    }

    public function test_handle_webhook_revalidates_via_validator(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'status' => 'VALID',
            'tran_id' => 'TRAN-1',
            'bank_tran_id' => 'BANK-1',
            'amount' => '100.00',
            'currency' => 'BDT',
        ]));
        $g = new SslcommerzGateway($this->cfg(), $http);

        $body = http_build_query([
            'val_id' => 'VAL-1',
            'status' => 'VALID',
            'tran_id' => 'TRAN-1',
            'amount' => '100.00',
        ]);
        $r = $g->handleWebhook($body, []);

        $this->assertTrue($r['success']);
        $this->assertSame('payment.completed', $r['event']);
        $this->assertStringContainsString('val_id=VAL-1', $http->requests[0]['url']);
    }

    public function test_handle_webhook_rejects_status_other_than_valid(): void
    {
        $g = new SslcommerzGateway($this->cfg(), new InMemoryHttpClient());
        $body = http_build_query(['val_id' => 'VAL-1', 'status' => 'FAILED']);
        $r = $g->handleWebhook($body, []);
        $this->assertTrue($r['success']);
        $this->assertSame('payment.failed', $r['event']);
    }

    public function test_handle_webhook_requires_val_id(): void
    {
        $g = new SslcommerzGateway($this->cfg(), new InMemoryHttpClient());
        $r = $g->handleWebhook('foo=bar', []);
        $this->assertFalse($r['success']);
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private function cfg(array $overrides = []): array
    {
        return array_merge([
            'store_id' => 'store-id',
            'store_password' => 'store-pass',
            'sandbox' => true,
        ], $overrides);
    }

    private function makeInvoice(int $id, string $number, float $total): Invoice
    {
        $i = new Invoice();
        $i->id = $id;
        $i->number = $number;
        $i->total = $total;
        $i->currency = 'BDT';
        return $i;
    }
}
