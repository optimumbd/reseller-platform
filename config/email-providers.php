<?php

declare(strict_types=1);

return [
    'drivers' => [
        'mailcow' => ['class' => \App\Services\Email\MailcowProvider::class],
        'zimbra' => ['class' => \App\Services\Email\ZimbraProvider::class],
        'postfix' => ['class' => \App\Services\Email\PostfixProvider::class],
        'google' => ['class' => \App\Services\Email\GoogleWorkspaceProvider::class],
        'microsoft' => ['class' => \App\Services\Email\Microsoft365Provider::class],
    ],
];
