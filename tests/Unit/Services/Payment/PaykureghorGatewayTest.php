<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Payment;

use App\Models\Invoice;
use App\Services\Payment\Drivers\PaykureghorGateway;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryHttpClient;

final class PaykureghorGatewayTest extends TestCase
{
    public function test_name_is_paykureghor(): void
    {
        $g = new PaykureghorGateway([]);
        $this->assertSame('paykureghor', $g->name());
    }

    public function test_unconfigured_gateway_short_circuits(): void
    {
        $g = new PaykureghorGateway([], new InMemoryHttpClient());

        $r = $g->createCheckout($this->makeInvoice(1, 'INV-1', 100.0));
        $this->assertFalse($r['success']);
        $this->assertSame('Pay KureGhor is not configured', $r['error']);

        $v = $g->verify('TX-1');
        $this->assertFalse($v['success']);
        $this->assertSame('Pay KureGhor is not configured', $v['error']);
    }

    public function test_create_checkout_posts_json_with_required_headers(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'status' => true,
            'message' => 'Payment URL generated',
            'payment_url' => 'https://checkout.paykureghor.com/pay/PAY-ABC',
        ]));
        $g = new PaykureghorGateway($this->cfg(), $http);

        $r = $g->createCheckout(
            $this->makeInvoice(7, 'INV-7', 1234.50),
            ['customer_name' => 'John Doe', 'customer_email' => 'john@x.com'],
        );

        $this->assertTrue($r['success']);
        $this->assertSame('https://checkout.paykureghor.com/pay/PAY-ABC', $r['redirect_url']);
        $this->assertSame('PAY-ABC', $r['token']);
        $this->assertSame('INV-7', $r['intent_id']);

        $req = $http->lastRequest();
        $this->assertSame('POST', $req['method']);
        $this->assertSame('https://checkout.paykureghor.com/api/payment/create', $req['url']);
        $this->assertSame('app-key', $req['headers']['API-KEY']);
        $this->assertSame('secret-key', $req['headers']['SECRET-KEY']);
        $this->assertSame('brand-key', $req['headers']['BRAND-KEY']);
        $this->assertSame('application/json', $req['headers']['Content-Type']);

        $this->assertSame('John Doe', $req['body']['cus_name']);
        $this->assertSame('john@x.com', $req['body']['cus_email']);
        $this->assertSame('1234.5', $req['body']['amount']);
        $this->assertArrayHasKey('success_url', $req['body']);
        $this->assertArrayHasKey('cancel_url', $req['body']);
        $this->assertSame(7, $req['body']['metadata']['invoice_id']);
        $this->assertSame('INV-7', $req['body']['metadata']['invoice_number']);
    }

    public function test_create_checkout_formats_amount_without_trailing_zeros(): void
    {
        $http = new InMemoryHttpClient([
            InMemoryHttpClient::jsonResponse(200, ['status' => true, 'payment_url' => 'https://x/y/A']),
            InMemoryHttpClient::jsonResponse(200, ['status' => true, 'payment_url' => 'https://x/y/B']),
            InMemoryHttpClient::jsonResponse(200, ['status' => true, 'payment_url' => 'https://x/y/C']),
        ]);
        $g = new PaykureghorGateway($this->cfg(), $http);

        $g->createCheckout($this->makeInvoice(1, 'INV-1', 10.0));     // natural -> "10"
        $g->createCheckout($this->makeInvoice(2, 'INV-2', 10.50));    // .50 -> "10.5"
        $g->createCheckout($this->makeInvoice(3, 'INV-3', 10.6));     // .6 -> "10.6"

        $this->assertSame('10', $http->requests[0]['body']['amount']);
        $this->assertSame('10.5', $http->requests[1]['body']['amount']);
        $this->assertSame('10.6', $http->requests[2]['body']['amount']);
    }

    public function test_create_checkout_rejects_zero_amount(): void
    {
        $g = new PaykureghorGateway($this->cfg(), new InMemoryHttpClient());
        $r = $g->createCheckout($this->makeInvoice(1, 'INV-1', 0.0));
        $this->assertFalse($r['success']);
        $this->assertSame('Invalid amount', $r['error']);
    }

    public function test_create_checkout_surfaces_api_error(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'status' => false,
            'message' => 'Invalid brand key',
        ]));
        $g = new PaykureghorGateway($this->cfg(), $http);

        $r = $g->createCheckout($this->makeInvoice(1, 'INV-1', 100.0));
        $this->assertFalse($r['success']);
        $this->assertSame('Invalid brand key', $r['error']);
    }

    public function test_create_checkout_surfaces_http_error(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(401, [
            'status' => false,
            'message' => 'Unauthorized',
        ]));
        $g = new PaykureghorGateway($this->cfg(), $http);

        $r = $g->createCheckout($this->makeInvoice(1, 'INV-1', 100.0));
        $this->assertFalse($r['success']);
        $this->assertSame('Unauthorized', $r['error']);
    }

    public function test_create_checkout_passes_through_explicit_metadata(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'status' => true,
            'payment_url' => 'https://checkout.paykureghor.com/pay/X',
        ]));
        $g = new PaykureghorGateway($this->cfg(), $http);

        $g->createCheckout(
            $this->makeInvoice(1, 'INV-1', 50.0),
            ['metadata' => ['phone' => '0151234', 'order_id' => 'O-1']],
        );

        $req = $http->lastRequest();
        $this->assertSame(['phone' => '0151234', 'order_id' => 'O-1'], $req['body']['metadata']);
    }

    public function test_verify_calls_verify_endpoint_with_transaction_id(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'cus_name' => 'John Doe',
            'cus_email' => 'john@x.com',
            'amount' => '900.000',
            'transaction_id' => 'OVKPXW165414',
            'metadata' => ['phone' => '015****'],
            'payment_method' => 'bkash',
            'status' => 'COMPLETED',
        ]));
        $g = new PaykureghorGateway($this->cfg(), $http);

        $r = $g->verify('OVKPXW165414');
        $this->assertTrue($r['success']);
        $this->assertSame('OVKPXW165414', $r['transaction_id']);
        $this->assertSame(900.0, $r['amount']);
        $this->assertSame('bkash', $r['payment_method']);
        $this->assertSame(['phone' => '015****'], $r['metadata']);

        $req = $http->lastRequest();
        $this->assertSame('POST', $req['method']);
        $this->assertSame('https://checkout.paykureghor.com/api/payment/verify', $req['url']);
        $this->assertSame(['transaction_id' => 'OVKPXW165414'], $req['body']);
    }

    public function test_verify_rejects_non_completed_status(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'transaction_id' => 'TX-1',
            'status' => 'PENDING',
        ]));
        $g = new PaykureghorGateway($this->cfg(), $http);

        $r = $g->verify('TX-1');
        $this->assertFalse($r['success']);
        $this->assertSame('Status PENDING', $r['error']);
    }

    public function test_verify_requires_transaction_id(): void
    {
        $g = new PaykureghorGateway($this->cfg(), new InMemoryHttpClient());
        $r = $g->verify('');
        $this->assertFalse($r['success']);
        $this->assertSame('transaction_id is required', $r['error']);
    }

    public function test_capture_round_trips_through_verify(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'transaction_id' => 'TX-1',
            'amount' => 100.0,
            'status' => 'COMPLETED',
        ]));
        $g = new PaykureghorGateway($this->cfg(), $http);

        $r = $g->capture('TX-1', 100.0);
        $this->assertTrue($r['success']);
        $this->assertSame('TX-1', $r['transaction_id']);
    }

    public function test_capture_rejects_amount_mismatch(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'transaction_id' => 'TX-1',
            'amount' => 100.0,
            'status' => 'COMPLETED',
        ]));
        $g = new PaykureghorGateway($this->cfg(), $http);

        $r = $g->capture('TX-1', 999.0);
        $this->assertFalse($r['success']);
        $this->assertStringContainsString('Amount mismatch', $r['error']);
    }

    public function test_refund_returns_unsupported_error(): void
    {
        $g = new PaykureghorGateway($this->cfg(), new InMemoryHttpClient());
        $r = $g->refund('TX-1', 50.0, 'duplicate');
        $this->assertFalse($r['success']);
        $this->assertStringContainsString('refunds are not exposed via API', $r['error']);
    }

    public function test_handle_webhook_query_string_revalidates(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'transaction_id' => 'OVKPXW165414',
            'amount' => 900.0,
            'status' => 'COMPLETED',
        ]));
        $g = new PaykureghorGateway($this->cfg(), $http);

        $body = http_build_query([
            'transactionId' => 'OVKPXW165414',
            'paymentMethod' => 'bkash',
            'paymentAmount' => '900.00',
            'paymentFee' => '10.00',
            'status' => 'success',
        ]);
        $r = $g->handleWebhook($body, []);

        $this->assertTrue($r['success']);
        $this->assertSame('payment.completed', $r['event']);
        $this->assertSame('OVKPXW165414', $r['payload']['transactionId']);
        $this->assertSame('OVKPXW165414', $r['payload']['transaction_id']);
        $this->assertSame('https://checkout.paykureghor.com/api/payment/verify', $http->lastRequest()['url']);
    }

    public function test_handle_webhook_json_body_works(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'transaction_id' => 'TX-1',
            'amount' => 50.0,
            'status' => 'COMPLETED',
        ]));
        $g = new PaykureghorGateway($this->cfg(), $http);

        $r = $g->handleWebhook(
            json_encode(['transactionId' => 'TX-1', 'status' => 'success']) ?: '',
            [],
        );
        $this->assertTrue($r['success']);
        $this->assertSame('payment.completed', $r['event']);
    }

    public function test_handle_webhook_non_success_does_not_revalidate(): void
    {
        $http = new InMemoryHttpClient();
        $g = new PaykureghorGateway($this->cfg(), $http);

        $body = http_build_query(['transactionId' => 'TX-1', 'status' => 'failed']);
        $r = $g->handleWebhook($body, []);

        $this->assertTrue($r['success']);
        $this->assertSame('payment.failed', $r['event']);
        $this->assertCount(0, $http->requests);
    }

    public function test_handle_webhook_requires_transaction_id(): void
    {
        $g = new PaykureghorGateway($this->cfg(), new InMemoryHttpClient());
        $r = $g->handleWebhook('status=success', []);
        $this->assertFalse($r['success']);
        $this->assertSame('Missing transactionId', $r['error']);
    }

    public function test_base_url_can_be_overridden(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'status' => true,
            'payment_url' => 'https://staging.paykureghor.com/pay/X',
        ]));
        $g = new PaykureghorGateway($this->cfg(['base_url' => 'https://staging.paykureghor.com/']), $http);

        $g->createCheckout($this->makeInvoice(1, 'INV-1', 10.0));
        $req = $http->lastRequest();
        $this->assertSame('https://staging.paykureghor.com/api/payment/create', $req['url']);
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
