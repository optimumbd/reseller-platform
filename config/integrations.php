<?php

declare(strict_types=1);

return [
    'live_chat' => [
        'driver' => env('LIVECHAT_DRIVER', ''), // tawk | crisp
        'site_id' => env('LIVECHAT_SITE_ID'),
    ],
    'cdn' => [
        'driver' => env('CDN_DRIVER', ''), // cloudflare | bunny
        'api_token' => env('CDN_API_TOKEN'),
        'zone_id' => env('CDN_ZONE_ID'),
    ],
];
