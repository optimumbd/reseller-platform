<?php

declare(strict_types=1);

return [
    'default' => env('REGISTRAR_DRIVER', 'mock'),

    'drivers' => [
        'mock' => [
            'class' => \App\Services\Registrar\MockRegistrar::class,
        ],
        'namecheap' => [
            'class' => \App\Services\Registrar\NamecheapRegistrar::class,
            'api_user' => env('NAMECHEAP_API_USER'),
            'api_key' => env('NAMECHEAP_API_KEY'),
            'username' => env('NAMECHEAP_USERNAME'),
            'client_ip' => env('NAMECHEAP_CLIENT_IP'),
            'sandbox' => filter_var(env('NAMECHEAP_SANDBOX', true), FILTER_VALIDATE_BOOLEAN),
        ],
        'spaceship' => [
            'class' => \App\Services\Registrar\Drivers\SpaceshipRegistrar::class,
            'api_key' => env('SPACESHIP_API_KEY'),
            'api_secret' => env('SPACESHIP_API_SECRET'),
            'base_url' => env('SPACESHIP_BASE_URL'),
        ],
        'cloudflare' => [
            'class' => \App\Services\Registrar\CloudflareRegistrar::class,
            'api_token' => env('CLOUDFLARE_API_TOKEN'),
            'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        ],
        'opensrs' => [
            'class' => \App\Services\Registrar\OpenSRSRegistrar::class,
            'username' => env('OPENSRS_USERNAME'),
            'api_key' => env('OPENSRS_API_KEY'),
            'sandbox' => filter_var(env('OPENSRS_SANDBOX', true), FILTER_VALIDATE_BOOLEAN),
        ],
    ],
];
