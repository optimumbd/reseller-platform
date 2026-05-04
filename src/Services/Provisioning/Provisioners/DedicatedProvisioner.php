<?php

declare(strict_types=1);

namespace App\Services\Provisioning\Provisioners;

use App\Models\Service;
use App\Services\Provisioning\ProvisionerInterface;

final class DedicatedProvisioner implements ProvisionerInterface
{
    public function provision(Service $service, array $payload = []): array { return ['success' => true]; }
    public function suspend(Service $service): array { return ['success' => true]; }
    public function unsuspend(Service $service): array { return ['success' => true]; }
    public function terminate(Service $service): array { return ['success' => true]; }
}
