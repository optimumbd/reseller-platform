<?php

declare(strict_types=1);

namespace App\Services\Provisioning;

use App\Models\ProvisioningJob;
use App\Models\Service;
use App\Services\Provisioning\Provisioners\DedicatedProvisioner;
use App\Services\Provisioning\Provisioners\DomainProvisioner;
use App\Services\Provisioning\Provisioners\EmailProvisioner;
use App\Services\Provisioning\Provisioners\HostingProvisioner;
use App\Services\Provisioning\Provisioners\SslProvisioner;
use App\Services\Provisioning\Provisioners\VpsProvisioner;

/**
 * Drives every product type through the same lifecycle:
 *   pending → provisioning → active → suspended → terminated
 */
final class ServiceLifecycleManager
{
    public function provision(Service $service, array $payload = []): array
    {
        $service->status = 'provisioning';
        $service->save();
        $job = new ProvisioningJob([
            'service_id' => (int) $service->id,
            'action' => 'provision',
            'status' => 'running',
            'payload' => json_encode($payload),
            'started_at' => now(),
        ]);
        $job->save();

        try {
            $provisioner = $this->provisionerFor((string) $service->type);
            $result = $provisioner->provision($service, $payload);
            if ($result['success'] ?? false) {
                $service->status = 'active';
                $service->starts_at = $service->starts_at ?: now();
                $service->save();
                $job->status = 'completed';
            } else {
                $service->status = 'failed';
                $service->save();
                $job->status = 'failed';
                $job->error = $result['message'] ?? 'unknown error';
            }
            $job->completed_at = now();
            $job->save();
            return $result;
        } catch (\Throwable $e) {
            $service->status = 'failed';
            $service->save();
            $job->status = 'failed';
            $job->error = $e->getMessage();
            $job->completed_at = now();
            $job->save();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function suspend(Service $service): array
    {
        $r = $this->provisionerFor((string) $service->type)->suspend($service);
        $service->status = 'suspended';
        $service->suspended_at = now();
        $service->save();
        return $r;
    }

    public function unsuspend(Service $service): array
    {
        $r = $this->provisionerFor((string) $service->type)->unsuspend($service);
        $service->status = 'active';
        $service->suspended_at = null;
        $service->save();
        return $r;
    }

    public function terminate(Service $service): array
    {
        $r = $this->provisionerFor((string) $service->type)->terminate($service);
        $service->status = 'terminated';
        $service->terminated_at = now();
        $service->save();
        return $r;
    }

    public function provisionerFor(string $type): ProvisionerInterface
    {
        return match ($type) {
            'domain' => new DomainProvisioner(),
            'hosting' => new HostingProvisioner(),
            'email' => new EmailProvisioner(),
            'ssl' => new SslProvisioner(),
            'vps' => new VpsProvisioner(),
            'dedicated' => new DedicatedProvisioner(),
            default => new DomainProvisioner(),
        };
    }
}
