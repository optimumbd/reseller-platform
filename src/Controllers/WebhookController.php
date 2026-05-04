<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\Payment\PaymentGatewayFactory;

final class WebhookController extends BaseController
{
    public function handle(Request $request, string $gateway): Response
    {
        $rawBody = (string) file_get_contents('php://input');
        $headers = $request->headers;
        $driver = PaymentGatewayFactory::make($gateway);
        $result = $driver->handleWebhook($rawBody, $headers);
        return $this->json($result, ($result['success'] ?? false) ? 200 : 400);
    }
}
