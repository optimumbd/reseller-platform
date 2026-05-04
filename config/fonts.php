<?php

declare(strict_types=1);

return [
    'default' => 'inter',
    'fonts' => [
        'inter' => [
            'name' => 'Inter',
            'family' => "'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif",
            'cdn' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        ],
        'tiro_bangla' => [
            'name' => 'Tiro Bangla',
            'family' => "'Tiro Bangla', 'SolaimanLipi', 'Kalpurush', sans-serif",
            'cdn' => 'https://fonts.googleapis.com/css2?family=Tiro+Bangla&display=swap',
        ],
    ],
    'auto_load' => [
        'bn' => 'tiro_bangla',
        'en' => 'inter',
    ],
];
