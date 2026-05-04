<?php

declare(strict_types=1);

return [
    'default_currency' => env('CURRENCY_DEFAULT', 'USD'),

    'gateways' => [
        'manual' => [
            'class' => \App\Services\Payment\ManualPayment::class,
            'enabled' => true,
            'instructions' => 'Send payment to bank account: 1234-5678-9012',
        ],
        'stripe' => [
            'class' => \App\Services\Payment\StripePayment::class,
            'enabled' => (bool) env('STRIPE_SECRET_KEY'),
            'public_key' => env('STRIPE_PUBLIC_KEY'),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
        'paypal' => [
            'class' => \App\Services\Payment\PayPalPayment::class,
            'enabled' => (bool) env('PAYPAL_CLIENT_ID'),
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'mode' => env('PAYPAL_MODE', 'sandbox'),
        ],
        'razorpay' => [
            'class' => \App\Services\Payment\RazorpayPayment::class,
            'enabled' => (bool) env('RAZORPAY_KEY_ID'),
            'key_id' => env('RAZORPAY_KEY_ID'),
            'key_secret' => env('RAZORPAY_KEY_SECRET'),
        ],
        'sslcommerz' => [
            'class' => \App\Services\Payment\SslcommerzPayment::class,
            'enabled' => (bool) env('SSLCOMMERZ_STORE_ID'),
            'store_id' => env('SSLCOMMERZ_STORE_ID'),
            'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
            'sandbox' => filter_var(env('SSLCOMMERZ_SANDBOX', true), FILTER_VALIDATE_BOOLEAN),
        ],
        'bkash' => [
            'class' => \App\Services\Payment\BkashPayment::class,
            'enabled' => (bool) env('BKASH_APP_KEY'),
            'app_key' => env('BKASH_APP_KEY'),
            'app_secret' => env('BKASH_APP_SECRET'),
            'username' => env('BKASH_USERNAME'),
            'password' => env('BKASH_PASSWORD'),
            'sandbox' => filter_var(env('BKASH_SANDBOX', true), FILTER_VALIDATE_BOOLEAN),
        ],
        'nagad' => [
            'class' => \App\Services\Payment\NagadPayment::class,
            'enabled' => (bool) env('NAGAD_MERCHANT_ID'),
            'merchant_id' => env('NAGAD_MERCHANT_ID'),
            'merchant_number' => env('NAGAD_MERCHANT_NUMBER'),
            'public_key' => env('NAGAD_PUBLIC_KEY'),
            'private_key' => env('NAGAD_PRIVATE_KEY'),
            'sandbox' => filter_var(env('NAGAD_SANDBOX', true), FILTER_VALIDATE_BOOLEAN),
        ],
        'paykureghor' => [
            'class' => \App\Services\Payment\Drivers\PaykureghorGateway::class,
            'enabled' => (bool) env('PAYKUREGHOR_API_KEY'),
            'api_key' => env('PAYKUREGHOR_API_KEY'),
            'secret_key' => env('PAYKUREGHOR_SECRET_KEY'),
            'brand_key' => env('PAYKUREGHOR_BRAND_KEY'),
            'base_url' => env('PAYKUREGHOR_BASE_URL'),
        ],
        'rokopay' => [
            'class' => \App\Services\Payment\Drivers\RokopayGateway::class,
            'enabled' => (bool) env('ROKOPAY_API_KEY'),
            'api_key' => env('ROKOPAY_API_KEY'),
            'secret_key' => env('ROKOPAY_SECRET_KEY'),
            'brand_key' => env('ROKOPAY_BRAND_KEY'),
            'base_url' => env('ROKOPAY_BASE_URL'),
        ],
        'wallet' => [
            'class' => \App\Services\Payment\WalletPayment::class,
            'enabled' => true,
        ],
    ],
];
