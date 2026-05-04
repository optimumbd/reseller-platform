<?php

declare(strict_types=1);

return [
    'driver' => env('CAPTCHA_DRIVER', ''),
    'site_key' => env('CAPTCHA_SITE_KEY'),
    'secret_key' => env('CAPTCHA_SECRET_KEY'),
];
