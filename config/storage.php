<?php

declare(strict_types=1);

return [
    'driver' => env('STORAGE_DRIVER', 'local'),
    'local' => [
        'root' => dirname(__DIR__) . '/storage/uploads',
    ],
    's3' => [
        'bucket' => env('S3_BUCKET'),
        'region' => env('S3_REGION'),
        'key' => env('S3_KEY'),
        'secret' => env('S3_SECRET'),
        'endpoint' => env('S3_ENDPOINT'),
    ],
];
