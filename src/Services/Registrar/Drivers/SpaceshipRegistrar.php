<?php

declare(strict_types=1);

namespace App\Services\Registrar\Drivers;

use App\Services\Registrar\Contracts\RegistrarInterface;

final class SpaceshipRegistrar implements RegistrarInterface
{
    public function checkAvailability(string $domain): array { return ['available' => true]; }
    public function register(string $domain, int $years, array $contacts, array $nameservers = [], array $extra = []): array { return ['success' => true]; }
    public function renew(string $domain, int $years): array { return ['success' => true]; }
    public function transfer(string $domain, string $authCode, array $contacts): array { return ['success' => true]; }
    public function getNameservers(string $domain): array { return []; }
    public function setNameservers(string $domain, array $hosts): bool { return true; }
    public function getEppCode(string $domain): ?string { return null; }
    public function setLock(string $domain, bool $locked): bool { return true; }
    public function setPrivacy(string $domain, bool $enabled): bool { return true; }
    public function setAutoRenew(string $domain, bool $enabled): bool { return true; }
    public function listDnsRecords(string $domain): array { return []; }
    public function setDnsRecords(string $domain, array $records): bool { return true; }
}
