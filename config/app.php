<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Reseller Platform'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'url' => env('APP_URL', 'http://localhost'),
    'key' => env('APP_KEY', ''),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => 'en',
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'theme' => 'default',
    'session_name' => 'rp_session',
    'session_lifetime' => 7200,
    'maintenance' => filter_var(env('MAINTENANCE_MODE', false), FILTER_VALIDATE_BOOLEAN),

    'middleware_aliases' => [
        'auth'         => \App\Middleware\AuthMiddleware::class,
        'guest'        => \App\Middleware\GuestMiddleware::class,
        'admin'        => \App\Middleware\AdminMiddleware::class,
        'reseller'     => \App\Middleware\ResellerMiddleware::class,
        'api'          => \App\Middleware\ApiAuthMiddleware::class,
        'csrf'         => \App\Middleware\CsrfMiddleware::class,
        'throttle'     => \App\Middleware\ThrottleMiddleware::class,
        'verified'     => \App\Middleware\VerificationMiddleware::class,
        'kyc'          => \App\Middleware\KycMiddleware::class,
        'twofactor'    => \App\Middleware\TwoFactorMiddleware::class,
        'maintenance'  => \App\Middleware\MaintenanceMiddleware::class,
        'install'      => \App\Middleware\InstallMiddleware::class,
        'locale'       => \App\Middleware\LocaleMiddleware::class,
        'currency'     => \App\Middleware\CurrencyMiddleware::class,
        'tenant'       => \App\Middleware\TenantMiddleware::class,
        'cors'         => \App\Middleware\CorsMiddleware::class,
        'feature'      => \App\Middleware\FeatureFlagMiddleware::class,
        'audit'        => \App\Middleware\ActivityLoggerMiddleware::class,
        'webhook.sig'  => \App\Middleware\WebhookSignatureMiddleware::class,
        'admin.ip'     => \App\Middleware\IpWhitelistMiddleware::class,
        'dunning'      => \App\Middleware\DunningGateMiddleware::class,
    ],

    'global_middleware' => [
        \App\Middleware\MaintenanceMiddleware::class,
        \App\Middleware\LocaleMiddleware::class,
        \App\Middleware\CurrencyMiddleware::class,
    ],
];
