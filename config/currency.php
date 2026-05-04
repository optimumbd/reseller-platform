<?php

declare(strict_types=1);

return [
    'default' => env('CURRENCY_DEFAULT', 'USD'),
    'fx_provider' => env('CURRENCY_FX_PROVIDER', 'exchangerate.host'),
    'supported' => ['USD', 'EUR', 'GBP', 'BDT', 'INR', 'JPY', 'CAD', 'AUD'],
];
