<?php

declare(strict_types=1);

return [
    'drivers' => [
        'hetzner_robot' => ['class' => \App\Services\Dedicated\HetznerRobotProvider::class],
        'ovh' => ['class' => \App\Services\Dedicated\OvhProvider::class],
        'phoenixnap' => ['class' => \App\Services\Dedicated\PhoenixNapProvider::class],
    ],
];
