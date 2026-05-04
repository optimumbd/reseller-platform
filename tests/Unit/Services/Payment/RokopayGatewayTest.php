<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Payment;

use App\Models\Invoice;
use App\Services\Payment\Drivers\RokopayGateway;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryHttpClient;

/**
 * RokoPay shares the entire API surface with Pay KureGhor (same endpoints,
 * same auth headers, same payload shape) - only the host differs. The
 * exhaustive tests already live in PaykureghorGatewayTest; here we just
 * lock in the alias-specific differences (driver name, brand label in
 * error messages, base URL) so a future refactor doesn't accidentally
 * collapse the two together.
 */
final class RokopayGatewayTest extends TestCase
{
    public function test_name_is_rokopay(): void
    {
        $g = new RokopayGateway([]);
        $this->assertSame('rokopay', $g->name());
    }

    public function test_default_base_url_is_pay_rokopay_com(): void
    {
        $g = new RokopayGateway($this->cfg());
        $this->assertSame('https://pay.rokopay.com', $g->baseUrl());
    }

    public function test_base_url_can_be_overridden(): void
    {
        $g = new RokopayGateway($this->cfg(['base_url' => 'https://staging.rokopay.com/']));
        $this->assertSame('https://staging.rokopay.com', $g->baseUrl());
    }

    public function test_unconfigured_error_uses_rokopay_brand_label(): void
    {
        $g = new RokopayGateway([], new InMemoryHttpClient());
        $r = $g->createCheckout($this->makeInvoice(1, 'INV-1', 100.0));
        $this->assertFalse($r['success']);
        $this->assertSame('RokoPay is not configured', $r['error']);
    }

    public function test_create_checkout_hits_rokopay_host_with_required_headers(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'status' => true,
            'payment_url' => 'https://pay.rokopay.com/pay/PAY-XYZ',
        ]));
        $g = new RokopayGateway($this->cfg(), $http);

        $r = $g->createCheckout(
            $this->makeInvoice(7, 'INV-7', 1234.50),
            ['customer_name' => 'John Doe', 'customer_email' => 'john@x.com'],
        );

        $this->assertTrue($r['success']);
        $this->assertSame('https://pay.rokopay.com/pay/PAY-XYZ', $r['redirect_url']);
        $this->assertSame('PAY-XYZ', $r['token']);

        $req = $http->lastRequest();
        $this->assertSame('POST', $req['method']);
        $this->assertSame('https://pay.rokopay.com/api/payment/create', $req['url']);
        $this->assertSame('app-key', $req['headers']['API-KEY']);
        $this->assertSame('secret-key', $req['headers']['SECRET-KEY']);
        $this->assertSame('brand-key', $req['headers']['BRAND-KEY']);
        $this->assertSame('application/json', $req['headers']['Content-Type']);
        $this->assertSame('1234.5', $req['body']['amount']);
        $this->assertStringContainsString('/webhooks/rokopay', $req['body']['success_url']);
        $this->assertStringContainsString('/webhooks/rokopay', $req['body']['cancel_url']);
    }

    public function test_create_checkout_surfaces_api_error_with_rokopay_label(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'status' => false,
            'message' => 'Invalid brand key',
        ]));
        $g = new RokopayGateway($this->cfg(), $http);
        $r = $g->createCheckout($this->makeInvoice(1, 'INV-1', 100.0));
        $this->assertFalse($r['success']);
        $this->assertSame('Invalid brand key', $r['error']);
    }

    public function test_verify_calls_rokopay_verify_endpoint(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'transaction_id' => 'TX-1',
            'amount' => 50.0,
            'payment_method' => 'bkash',
            'status' => 'COMPLETED',
        ]));
        $g = new RokopayGateway($this->cfg(), $http);

        $r = $g->verify('TX-1');
        $this->assertTrue($r['success']);
        $this->assertSame('TX-1', $r['transaction_id']);
        $this->assertSame('https://pay.rokopay.com/api/payment/verify', $http->lastRequest()['url']);
    }

    public function test_handle_webhook_revalidates_against_rokopay_host(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'transaction_id' => 'TX-1',
            'amount' => 100.0,
            'status' => 'COMPLETED',
        ]));
        $g = new RokopayGateway($this->cfg(), $http);

        $body = http_build_query(['transactionId' => 'TX-1', 'status' => 'success']);
        $r = $g->handleWebhook($body, []);
        $this->assertTrue($r['success']);
        $this->assertSame('payment.completed', $r['event']);
        $this->assertStringContainsString('pay.rokopay.com/api/payment/verify', $http->lastRequest()['url']);
    }

    public function test_refund_returns_unsupported_error_with_rokopay_label(): void
    {
        $g = new RokopayGateway($this->cfg(), new InMemoryHttpClient());
        $r = $g->refund('TX-1', 50.0);
        $this->assertFalse($r['success']);
        $this->assertStringStartsWith('RokoPay refunds are not exposed', $r['error']);
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private function cfg(array $overrides = []): array
    {
        return array_merge([
            'api_key' => 'app-key',
            'secret_key' => 'secret-key',
            'brand_key' => 'brand-key',
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
