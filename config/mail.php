<?php

declare(strict_types=1);

return [
    'driver' => env('MAIL_DRIVER', 'smtp'),
    'host' => env('MAIL_HOST', 'localhost'),
    'port' => (int) env('MAIL_PORT', 587),
    'user' => env('MAIL_USER', ''),
    'pass' => env('MAIL_PASS', ''),
    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    'from_address' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
    'from_name' => env('MAIL_FROM_NAME', 'Reseller Platform'),
];
