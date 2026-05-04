<?php

declare(strict_types=1);

namespace App\Services\Registrar\Drivers;

use App\Services\Registrar\Contracts\RegistrarInterface;

/**
 * Mock registrar — answers everything as "available", "registered", etc. so
 * the platform is fully usable in development with no external API.
 */
final class MockRegistrar implements RegistrarInterface
{
    public function checkAvailability(string $domain): array
    {
        // "Pretend" certain example names are taken so the UX shows both states.
        $taken = ['google', 'facebook', 'amazon', 'twitter', 'apple', 'microsoft'];
        $sld = strtolower(explode('.', $domain)[0] ?? '');
        return [
            'available' => !in_array($sld, $taken, true),
            'premium' => false,
            'currency' => 'USD',
        ];
    }

    public function register(string $domain, int $years, array $contacts, array $nameservers = [], array $extra = []): array
    {
        return [
            'success' => true,
            'registrar_id' => 'MOCK-' . strtoupper(bin2hex(random_bytes(4))),
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$years} years") ?: time()),
        ];
    }

    public function renew(string $domain, int $years): array
    {
        return ['success' => true, 'expires_at' => date('Y-m-d H:i:s', strtotime("+{$years} years") ?: time())];
    }

    public function transfer(string $domain, string $authCode, array $contacts): array
    {
        return ['success' => true, 'transfer_id' => 'MOCK-TR-' . bin2hex(random_bytes(4))];
    }

    public function getNameservers(string $domain): array
    {
        return ['ns1.example.com', 'ns2.example.com'];
    }

    public function setNameservers(string $domain, array $hosts): bool
    {
        return true;
    }

    public function getEppCode(string $domain): ?string
    {
        return strtoupper(bin2hex(random_bytes(8)));
    }

    public function setLock(string $domain, bool $locked): bool
    {
        return true;
    }

    public function setPrivacy(string $domain, bool $enabled): bool
    {
        return true;
    }

    public function setAutoRenew(string $domain, bool $enabled): bool
    {
        return true;
    }

    public function listDnsRecords(string $domain): array
    {
        return [];
    }

    public function setDnsRecords(string $domain, array $records): bool
    {
        return true;
    }
}
