<?php

declare(strict_types=1);

return [
    'drivers' => [
        'virtualizor' => ['class' => \App\Services\Vps\VirtualizorProvider::class],
        'solusvm' => ['class' => \App\Services\Vps\SolusVmProvider::class],
        'proxmox' => ['class' => \App\Services\Vps\ProxmoxProvider::class],
        'digitalocean' => ['class' => \App\Services\Vps\DigitalOceanProvider::class],
        'hetzner' => ['class' => \App\Services\Vps\HetznerProvider::class],
        'linode' => ['class' => \App\Services\Vps\LinodeProvider::class],
        'vultr' => ['class' => \App\Services\Vps\VultrProvider::class],
    ],
];
