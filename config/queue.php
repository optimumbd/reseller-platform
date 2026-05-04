<?php

declare(strict_types=1);

return [
    'driver' => env('QUEUE_DRIVER', 'database'),
    'default_queue' => 'default',
    'max_attempts' => 5,
    'retry_after' => 90,
];
