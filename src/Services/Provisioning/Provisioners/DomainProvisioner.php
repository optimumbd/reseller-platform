<?php

declare(strict_types=1);

namespace App\Services\Provisioning\Provisioners;

use App\Models\Domain;
use App\Models\Service;
use App\Services\Provisioning\ProvisionerInterface;
use App\Services\Registrar\RegistrarFactory;

final class DomainProvisioner implements ProvisionerInterface
{
    public function provision(Service $service, array $payload = []): array
    {
        $registrar = RegistrarFactory::default();
        $domain = (string) ($payload['domain'] ?? $service->label);
        $years = (int) ($payload['years'] ?? 1);
        $contacts = (array) ($payload['contacts'] ?? []);
        $nameservers = (array) ($payload['nameservers'] ?? []);

        $result = $registrar->register($domain, $years, $contacts, $nameservers);
        if (!($result['success'] ?? false)) {
            return ['success' => false, 'message' => $result['error'] ?? 'register failed'];
        }
        $row = new Domain([
            'user_id' => (int) $service->user_id,
            'service_id' => (int) $service->id,
            'domain' => $domain,
            'sld' => explode('.', $domain)[0] ?? $domain,
            'tld' => substr($domain, strpos($domain, '.') + 1),
            'registrar' => (string) (config('registrars.default') ?? 'mock'),
            'registrar_domain_id' => $result['registrar_id'] ?? null,
            'status' => 'active',
            'registered_at' => now(),
            'expires_at' => $result['expires_at'] ?? null,
        ]);
        $row->save();
        return ['success' => true, 'data' => $row->toArray()];
    }

    public function suspend(Service $service): array { return ['success' => true]; }
    public function unsuspend(Service $service): array { return ['success' => true]; }
    public function terminate(Service $service): array { return ['success' => true]; }
}
