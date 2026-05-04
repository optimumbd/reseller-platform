<?php

declare(strict_types=1);

return [
    'icann' => [
        'wdrp_annual_reminder' => true,
        'errp_30_15_5_days' => true,
        'verify_registrant_email_within_days' => 15,
        'rgp_redemption_window_days' => 30,
    ],
    'sanctions' => [
        'enabled' => true,
        'provider' => 'ofac',
    ],
    'aml' => ['enabled' => false, 'threshold_usd' => 10000],
    'pep' => ['enabled' => false],
];
