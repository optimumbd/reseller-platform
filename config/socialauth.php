<?php

declare(strict_types=1);

return [
    'providers' => [
        'google' => [
            'enabled' => (bool) env('GOOGLE_CLIENT_ID'),
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => '/auth/google/callback',
        ],
        'facebook' => [
            'enabled' => (bool) env('FACEBOOK_CLIENT_ID'),
            'client_id' => env('FACEBOOK_CLIENT_ID'),
            'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
            'redirect' => '/auth/facebook/callback',
        ],
        'github' => [
            'enabled' => (bool) env('GITHUB_CLIENT_ID'),
            'client_id' => env('GITHUB_CLIENT_ID'),
            'client_secret' => env('GITHUB_CLIENT_SECRET'),
            'redirect' => '/auth/github/callback',
        ],
    ],
];
