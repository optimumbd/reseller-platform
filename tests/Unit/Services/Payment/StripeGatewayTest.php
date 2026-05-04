<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Payment;

use App\Models\Invoice;
use App\Services\Payment\Drivers\StripeGateway;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryHttpClient;

final class StripeGatewayTest extends TestCase
{
    public function test_name_is_stripe(): void
    {
        $g = new StripeGateway([]);
        $this->assertSame('stripe', $g->name());
    }

    public function test_unconfigured_gateway_returns_error(): void
    {
        $g = new StripeGateway([], new InMemoryHttpClient());
        $invoice = $this->makeInvoice(1, 'INV-001', 12.34, 'usd');
        $result = $g->createCheckout($invoice);
        $this->assertFalse($result['success']);
        $this->assertSame('Stripe is not configured', $result['error']);
    }

    public function test_create_checkout_posts_form_encoded_to_stripe(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'id' => 'cs_test_123',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_123',
        ]));
        $g = new StripeGateway(['secret_key' => 'sk_test_xyz', 'webhook_secret' => 'whsec_xyz'], $http);
        $invoice = $this->makeInvoice(42, 'INV-042', 19.99, 'usd');
        $result = $g->createCheckout($invoice, ['customer_email' => 'a@b.com']);

        $this->assertTrue($result['success']);
        $this->assertSame('cs_test_123', $result['intent_id']);
        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_123', $result['redirect_url']);

        $req = $http->lastRequest();
        $this->assertNotNull($req);
        $this->assertSame('POST', $req['method']);
        $this->assertSame('https://api.stripe.com/v1/checkout/sessions', $req['url']);
        $this->assertSame('application/x-www-form-urlencoded', $req['headers']['Content-Type']);
        $this->assertSame('Bearer sk_test_xyz', $req['headers']['Authorization']);

        // The body must be Stripe-style form encoding (a[b][c]=v).
        parse_str($req['body'], $parsed);
        $this->assertSame('payment', $parsed['mode']);
        $this->assertSame('1999', $parsed['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('usd', $parsed['line_items'][0]['price_data']['currency']);
        $this->assertSame('a@b.com', $parsed['customer_email']);
        $this->assertSame('42', $parsed['metadata']['invoice_id']);
    }

    public function test_create_checkout_returns_error_on_stripe_error_payload(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(400, [
            'error' => ['message' => 'Invalid amount', 'code' => 'parameter_invalid'],
        ]));
        $g = new StripeGateway(['secret_key' => 'sk_test'], $http);
        $invoice = $this->makeInvoice(1, 'INV-001', 0.0, 'usd');
        $r = $g->createCheckout($invoice);
        $this->assertFalse($r['success']);
        $this->assertSame('Invalid amount', $r['error']);
    }

    public function test_capture_skips_http_when_reference_is_not_a_payment_intent(): void
    {
        $http = new InMemoryHttpClient();
        $g = new StripeGateway(['secret_key' => 'sk_test'], $http);
        $r = $g->capture('cs_test_123', 10.0);
        $this->assertTrue($r['success']);
        $this->assertSame('cs_test_123', $r['transaction_id']);
        $this->assertSame([], $http->requests, 'capture() should not hit the network for cs_ refs');
    }

    public function test_capture_calls_payment_intent_capture_endpoint(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, ['id' => 'pi_abc']));
        $g = new StripeGateway(['secret_key' => 'sk_test'], $http);
        $r = $g->capture('pi_abc', 25.50);
        $this->assertTrue($r['success']);
        $req = $http->lastRequest();
        $this->assertSame('https://api.stripe.com/v1/payment_intents/pi_abc/capture', $req['url']);
        parse_str($req['body'], $parsed);
        $this->assertSame('2550', $parsed['amount_to_capture']);
    }

    public function test_refund_targets_payment_intent_or_charge(): void
    {
        $http = new InMemoryHttpClient([
            InMemoryHttpClient::jsonResponse(200, ['id' => 're_1']),
            InMemoryHttpClient::jsonResponse(200, ['id' => 're_2']),
        ]);
        $g = new StripeGateway(['secret_key' => 'sk_test'], $http);

        $g->refund('pi_abc', 5.0, 'requested_by_customer');
        $g->refund('ch_xyz', 5.0);

        $first = $http->requests[0];
        $second = $http->requests[1];
        parse_str($first['body'], $a);
        parse_str($second['body'], $b);

        $this->assertSame('pi_abc', $a['payment_intent']);
        $this->assertSame('requested_by_customer', $a['reason']);
        $this->assertSame('ch_xyz', $b['charge']);
        $this->assertArrayNotHasKey('reason', $b);
    }

    public function test_webhook_verifies_signature(): void
    {
        $secret = 'whsec_secret';
        $body = '{"type":"checkout.session.completed","data":{}}';
        $now = time();
        $signed = hash_hmac('sha256', $now . '.' . $body, $secret);
        $header = 't=' . $now . ',v1=' . $signed;

        $g = new StripeGateway(['webhook_secret' => $secret]);
        $r = $g->handleWebhook($body, ['stripe-signature' => $header]);

        $this->assertTrue($r['success']);
        $this->assertSame('checkout.session.completed', $r['event']);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $g = new StripeGateway(['webhook_secret' => 'whsec_secret']);
        $r = $g->handleWebhook('{}', ['stripe-signature' => 't=' . time() . ',v1=deadbeef']);
        $this->assertFalse($r['success']);
        $this->assertSame('Invalid Stripe webhook signature', $r['error']);
    }

    public function test_webhook_rejects_stale_timestamp(): void
    {
        $secret = 'whsec_secret';
        $body = '{"type":"checkout.session.completed"}';
        $stale = time() - 3600;
        $sig = 't=' . $stale . ',v1=' . hash_hmac('sha256', $stale . '.' . $body, $secret);

        $g = new StripeGateway(['webhook_secret' => $secret]);
        $r = $g->handleWebhook($body, ['stripe-signature' => $sig]);
        $this->assertFalse($r['success']);
    }

    public function test_form_encoder_handles_nested_arrays_stripe_style(): void
    {
        $g = new StripeGateway([]);
        $encoded = $g->encodeForm([
            'mode' => 'payment',
            'metadata' => ['invoice_id' => '7', 'note' => 'a&b'],
            'line_items' => [['quantity' => 1, 'price_data' => ['currency' => 'usd', 'unit_amount' => 999]]],
        ]);
        parse_str($encoded, $parsed);

        $this->assertSame('payment', $parsed['mode']);
        $this->assertSame('7', $parsed['metadata']['invoice_id']);
        $this->assertSame('a&b', $parsed['metadata']['note']);
        $this->assertSame('999', $parsed['line_items'][0]['price_data']['unit_amount']);
    }

    private function makeInvoice(int $id, string $number, float $total, string $currency): Invoice
    {
        $invoice = new Invoice();
        $invoice->id = $id;
        $invoice->number = $number;
        $invoice->total = $total;
        $invoice->currency = $currency;
        return $invoice;
    }
}
