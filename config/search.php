<?php

declare(strict_types=1);

return [
    'driver' => env('SEARCH_DRIVER', 'database'),
    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
        'key' => env('MEILISEARCH_KEY'),
    ],
    'typesense' => [
        'host' => env('TYPESENSE_HOST', 'http://127.0.0.1:8108'),
        'key' => env('TYPESENSE_API_KEY'),
    ],
];
