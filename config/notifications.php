<?php

declare(strict_types=1);

return [
    'channels' => [
        'mail' => ['enabled' => true],
        'database' => ['enabled' => true],
        'telegram' => [
            'enabled' => (bool) env('TELEGRAM_BOT_TOKEN'),
            'bot_token' => env('TELEGRAM_BOT_TOKEN'),
            'chat_id' => env('TELEGRAM_CHAT_ID'),
        ],
        'whatsapp' => [
            'enabled' => (bool) env('WHATSAPP_API_TOKEN'),
            'api_url' => env('WHATSAPP_API_URL'),
            'api_token' => env('WHATSAPP_API_TOKEN'),
        ],
        'sms' => [
            'enabled' => (bool) env('SMS_API_KEY'),
            'driver' => env('SMS_DRIVER', 'twilio'),
            'api_key' => env('SMS_API_KEY'),
            'sender' => env('SMS_SENDER'),
        ],
        'push' => [
            'enabled' => false,
            'vapid_public' => env('PUSH_VAPID_PUBLIC'),
            'vapid_private' => env('PUSH_VAPID_PRIVATE'),
        ],
    ],
];
