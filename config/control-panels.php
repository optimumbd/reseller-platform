<?php

declare(strict_types=1);

return [
    'drivers' => [
        'cpanel' => ['class' => \App\Services\Hosting\CpanelControlPanel::class],
        'plesk' => ['class' => \App\Services\Hosting\PleskControlPanel::class],
        'directadmin' => ['class' => \App\Services\Hosting\DirectAdminControlPanel::class],
        'cyberpanel' => ['class' => \App\Services\Hosting\CyberPanelControlPanel::class],
        'hestia' => ['class' => \App\Services\Hosting\HestiaControlPanel::class],
    ],
];
