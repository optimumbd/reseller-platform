<?php

declare(strict_types=1);

return [
    'drivers' => [
        'sectigo' => ['class' => \App\Services\Ssl\SectigoProvider::class],
        'gogetssl' => ['class' => \App\Services\Ssl\GoGetSslProvider::class],
        'rapidssl' => ['class' => \App\Services\Ssl\RapidSslProvider::class],
        'digicert' => ['class' => \App\Services\Ssl\DigiCertProvider::class],
        'letsencrypt' => ['class' => \App\Services\Ssl\LetsEncryptProvider::class],
    ],
];
