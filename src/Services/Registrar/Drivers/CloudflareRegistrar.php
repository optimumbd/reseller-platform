<?php

declare(strict_types=1);

namespace App\Services\Registrar\Drivers;

use App\Core\Http\Client;
use App\Core\Http\HttpClientInterface;
use App\Core\Http\Response;
use App\Services\Registrar\Contracts\RegistrarInterface;

/**
 * Cloudflare driver — primarily a DNS / nameserver manager.
 *
 * Cloudflare's public registrar API is account-bound and does NOT expose
 * self-service domain registration / transfer / EPP retrieval. This driver
 * therefore implements:
 *
 *  - Full DNS CRUD via /zones/{id}/dns_records
 *  - Nameserver lookup via /zones/{id} (name_servers array)
 *  - Lock / privacy / auto-renew toggles via /accounts/{aid}/registrar/domains/{name}
 *
 * The registration / renewal / transfer methods return a graceful
 * `['success' => false, 'error' => 'unsupported']`; pair Cloudflare with
 * another registrar for those flows.
 */
final class CloudflareRegistrar implements RegistrarInterface
{
    private const API_BASE = 'https://api.cloudflare.com/client/v4';

    /** @var array<string,mixed> */
    private array $cfg;
    private HttpClientInterface $http;

    /** @var array<string,string> domain → zone_id memo */
    private array $zoneCache = [];

    public function __construct(?array $cfg = null, ?HttpClientInterface $http = null)
    {
        $this->cfg = $cfg ?? (array) (config('registrars.drivers.cloudflare') ?? config('registrars.cloudflare') ?? []);
        $this->http = $http ?? new Client();
    }

    public function checkAvailability(string $domain): array
    {
        if (!$this->isConfigured()) {
            return ['available' => false, 'error' => 'Cloudflare is not configured'];
        }
        $accountId = (string) ($this->cfg['account_id'] ?? '');
        if ($accountId === '') {
            return ['available' => false, 'error' => 'Cloudflare account_id not configured'];
        }
        $resp = $this->http->get(
            self::API_BASE . '/accounts/' . rawurlencode($accountId) . '/registrar/domains/' . rawurlencode($domain),
            $this->headers(),
        );
        $json = $resp->json();
        if (!is_array($json)) {
            return ['available' => false, 'error' => 'Cloudflare returned malformed response'];
        }
        if ($resp->status === 404 || (!$resp->ok() && $this->errorCode($json) === 1003)) {
            return ['available' => true, 'currency' => 'USD'];
        }
        if (!$resp->ok()) {
            return ['available' => false, 'error' => $this->errorMessage($json) ?? ('Cloudflare HTTP ' . $resp->status)];
        }
        $result = $json['result'] ?? [];
        return [
            'available' => empty($result['available']) ? false : true,
            'premium' => false,
            'price' => 0.0,
            'currency' => 'USD',
        ];
    }

    public function register(string $domain, int $years, array $contacts, array $nameservers = [], array $extra = []): array
    {
        return ['success' => false, 'error' => 'Cloudflare driver does not support domain registration via API'];
    }

    public function renew(string $domain, int $years): array
    {
        return ['success' => false, 'error' => 'Cloudflare driver does not support renewal via API'];
    }

    public function transfer(string $domain, string $authCode, array $contacts): array
    {
        return ['success' => false, 'error' => 'Cloudflare driver does not support transfer via API'];
    }

    public function getNameservers(string $domain): array
    {
        $zoneId = $this->resolveZone($domain);
        if ($zoneId === null) {
            return [];
        }
        $resp = $this->http->get(self::API_BASE . '/zones/' . rawurlencode($zoneId), $this->headers());
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json)) {
            return [];
        }
        $ns = $json['result']['name_servers'] ?? [];
        return is_array($ns) ? array_map('strval', $ns) : [];
    }

    public function setNameservers(string $domain, array $hosts): bool
    {
        // Custom NS for a Cloudflare-registered domain require the account-bound
        // registrar API. Other domains have NS set at the registrar of record.
        return false;
    }

    public function getEppCode(string $domain): ?string
    {
        return null;
    }

    public function setLock(string $domain, bool $locked): bool
    {
        return $this->updateRegistrarDomain($domain, ['locked' => $locked]);
    }

    public function setPrivacy(string $domain, bool $enabled): bool
    {
        return $this->updateRegistrarDomain($domain, ['privacy' => $enabled]);
    }

    public function setAutoRenew(string $domain, bool $enabled): bool
    {
        return $this->updateRegistrarDomain($domain, ['auto_renew' => $enabled]);
    }

    public function listDnsRecords(string $domain): array
    {
        $zoneId = $this->resolveZone($domain);
        if ($zoneId === null) {
            return [];
        }
        $records = [];
        $page = 1;
        do {
            $resp = $this->http->get(
                self::API_BASE . '/zones/' . rawurlencode($zoneId) . '/dns_records',
                $this->headers(),
                ['page' => $page, 'per_page' => 100],
            );
            $json = $resp->json();
            if (!$resp->ok() || !is_array($json) || empty($json['success'])) {
                break;
            }
            foreach ((array) ($json['result'] ?? []) as $r) {
                $records[] = [
                    'type' => (string) ($r['type'] ?? ''),
                    'name' => (string) ($r['name'] ?? ''),
                    'content' => (string) ($r['content'] ?? ''),
                    'ttl' => (int) ($r['ttl'] ?? 1),
                    'priority' => isset($r['priority']) ? (int) $r['priority'] : null,
                    'id' => (string) ($r['id'] ?? ''),
                    'proxied' => (bool) ($r['proxied'] ?? false),
                ];
            }
            $totalPages = (int) ($json['result_info']['total_pages'] ?? 1);
            $page++;
        } while ($page <= $totalPages);
        return $records;
    }

    /**
     * Replace-all semantics: delete every existing record, then create the new
     * set. Records may include an optional `proxied` boolean.
     */
    public function setDnsRecords(string $domain, array $records): bool
    {
        $zoneId = $this->resolveZone($domain);
        if ($zoneId === null) {
            return false;
        }
        // Delete existing
        foreach ($this->listDnsRecords($domain) as $existing) {
            $id = (string) ($existing['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $this->http->delete(
                self::API_BASE . '/zones/' . rawurlencode($zoneId) . '/dns_records/' . rawurlencode($id),
                $this->headers(),
            );
        }
        // Create new
        foreach ($records as $r) {
            $body = [
                'type' => strtoupper((string) ($r['type'] ?? 'A')),
                'name' => (string) ($r['name'] ?? '@'),
                'content' => (string) ($r['content'] ?? ''),
                'ttl' => (int) ($r['ttl'] ?? 1),
            ];
            if (isset($r['priority'])) {
                $body['priority'] = (int) $r['priority'];
            }
            if (isset($r['proxied'])) {
                $body['proxied'] = (bool) $r['proxied'];
            }
            $resp = $this->http->post(
                self::API_BASE . '/zones/' . rawurlencode($zoneId) . '/dns_records',
                $body,
                $this->headers(),
            );
            if (!$resp->ok()) {
                return false;
            }
        }
        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->cfg['api_token']);
    }

    /**
     * Resolve a domain (zone name) to a Cloudflare zone id, with caching.
     */
    public function resolveZone(string $domain): ?string
    {
        if (isset($this->zoneCache[$domain])) {
            return $this->zoneCache[$domain];
        }
        if (!$this->isConfigured()) {
            return null;
        }
        $resp = $this->http->get(
            self::API_BASE . '/zones',
            $this->headers(),
            ['name' => $domain],
        );
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json) || empty($json['result'])) {
            return null;
        }
        $zone = $json['result'][0] ?? null;
        if (!is_array($zone) || empty($zone['id'])) {
            return null;
        }
        return $this->zoneCache[$domain] = (string) $zone['id'];
    }

    /**
     * @param array<string,mixed> $patch
     */
    private function updateRegistrarDomain(string $domain, array $patch): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }
        $accountId = (string) ($this->cfg['account_id'] ?? '');
        if ($accountId === '') {
            return false;
        }
        $resp = $this->http->put(
            self::API_BASE . '/accounts/' . rawurlencode($accountId) . '/registrar/domains/' . rawurlencode($domain),
            $patch,
            $this->headers(),
        );
        if (!$resp->ok()) {
            return false;
        }
        $json = $resp->json();
        return is_array($json) && !empty($json['success']);
    }

    /**
     * @return array<string,string>
     */
    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . (string) ($this->cfg['api_token'] ?? ''),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * @param array<string,mixed> $json
     */
    private function errorMessage(array $json): ?string
    {
        $errors = $json['errors'] ?? null;
        if (!is_array($errors) || $errors === []) {
            return null;
        }
        $first = $errors[0] ?? null;
        if (is_array($first) && isset($first['message'])) {
            return (string) $first['message'];
        }
        return null;
    }

    /**
     * @param array<string,mixed> $json
     */
    private function errorCode(array $json): ?int
    {
        $errors = $json['errors'] ?? null;
        if (!is_array($errors) || $errors === []) {
            return null;
        }
        $first = $errors[0] ?? null;
        if (is_array($first) && isset($first['code'])) {
            return (int) $first['code'];
        }
        return null;
    }
}
