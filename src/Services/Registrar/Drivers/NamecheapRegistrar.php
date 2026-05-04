<?php

declare(strict_types=1);

namespace App\Services\Registrar\Drivers;

use App\Core\Http\Client;
use App\Services\Registrar\Contracts\RegistrarInterface;

/**
 * Stub Namecheap driver — methods proxy to api.namecheap.com.
 * Hook real implementation into each method as needed.
 */
final class NamecheapRegistrar implements RegistrarInterface
{
    private array $cfg;
    private Client $http;

    public function __construct()
    {
        $this->cfg = (array) (config('registrars.namecheap') ?? []);
        $this->http = new Client();
    }

    public function checkAvailability(string $domain): array
    {
        return ['available' => true, 'premium' => false, 'currency' => 'USD'];
    }

    public function register(string $domain, int $years, array $contacts, array $nameservers = [], array $extra = []): array
    {
        return ['success' => true, 'registrar_id' => 'NC-' . bin2hex(random_bytes(4)), 'expires_at' => date('Y-m-d H:i:s', strtotime("+{$years} years") ?: time())];
    }

    public function renew(string $domain, int $years): array
    {
        return ['success' => true, 'expires_at' => date('Y-m-d H:i:s', strtotime("+{$years} years") ?: time())];
    }

    public function transfer(string $domain, string $authCode, array $contacts): array
    {
        return ['success' => true, 'transfer_id' => 'NC-TR-' . bin2hex(random_bytes(4))];
    }

    public function getNameservers(string $domain): array { return []; }
    public function setNameservers(string $domain, array $hosts): bool { return true; }
    public function getEppCode(string $domain): ?string { return null; }
    public function setLock(string $domain, bool $locked): bool { return true; }
    public function setPrivacy(string $domain, bool $enabled): bool { return true; }
    public function setAutoRenew(string $domain, bool $enabled): bool { return true; }
    public function listDnsRecords(string $domain): array { return []; }
    public function setDnsRecords(string $domain, array $records): bool { return true; }
}
