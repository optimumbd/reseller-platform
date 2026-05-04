<?php

declare(strict_types=1);

namespace App\Services\Provisioning;

use App\Models\Service;

interface ProvisionerInterface
{
    /** @return array{success:bool,message?:string,data?:array} */
    public function provision(Service $service, array $payload = []): array;

    public function suspend(Service $service): array;

    public function unsuspend(Service $service): array;

    public function terminate(Service $service): array;
}
