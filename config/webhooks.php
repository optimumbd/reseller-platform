<?php

declare(strict_types=1);

return [
    'max_retries' => 5,
    'retry_backoff_seconds' => [60, 300, 900, 3600, 21600],
    'sign_secret' => env('WEBHOOK_SIGN_SECRET', 'change-me'),
    'events' => [
        'order.created', 'order.completed', 'order.cancelled',
        'invoice.created', 'invoice.paid', 'invoice.refunded',
        'service.created', 'service.activated', 'service.suspended', 'service.terminated',
        'domain.registered', 'domain.transferred', 'domain.renewed', 'domain.expired',
        'kyc.approved', 'kyc.rejected',
    ],
];
