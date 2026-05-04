<?php

declare(strict_types=1);

namespace App\Services\Registrar\Drivers;

use App\Core\Http\Client;
use App\Core\Http\HttpClientInterface;
use App\Core\Http\Response;
use App\Services\Registrar\Contracts\RegistrarInterface;

/**
 * Spaceship.com registrar driver.
 *
 *   API base:    https://spaceship.dev/api
 *   Auth:        X-Api-Key + X-Api-Secret headers
 *   Async ops:   register / renew / restore / transfer return 202 with a
 *                "spaceship-async-operationid" header. We surface the operation
 *                id; callers can poll {@see fetchOperation()}.
 *
 * Reference: https://docs.spaceship.dev/
 */
final class SpaceshipRegistrar implements RegistrarInterface
{
    private const API_BASE = 'https://spaceship.dev/api';
    private const DEFAULT_PAGE_SIZE = 500;

    /** @var array<string,mixed> */
    private array $cfg;
    private HttpClientInterface $http;

    public function __construct(?array $cfg = null, ?HttpClientInterface $http = null)
    {
        $this->cfg = $cfg ?? (array) (
            config('registrars.drivers.spaceship')
            ?? config('registrars.spaceship')
            ?? []
        );
        $this->http = $http ?? new Client();
    }

    public function checkAvailability(string $domain): array
    {
        if (!$this->isConfigured()) {
            return ['available' => false, 'error' => 'Spaceship is not configured'];
        }
        $resp = $this->http->get(
            $this->baseUrl() . '/v1/domains/' . rawurlencode($domain) . '/availability',
            $this->headers(),
        );
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json)) {
            return ['available' => false, 'error' => $this->errorMessage($json) ?? ('Spaceship HTTP ' . $resp->status)];
        }
        $available = (bool) ($json['isAvailable'] ?? $json['available'] ?? false);
        $premium = (bool) ($json['isPremium'] ?? $json['premium'] ?? false);
        $price = isset($json['price']) ? (float) $json['price'] : 0.0;
        $currency = (string) ($json['currency'] ?? 'USD');
        return [
            'available' => $available,
            'premium' => $premium,
            'price' => $price,
            'currency' => $currency,
        ];
    }

    public function register(string $domain, int $years, array $contacts, array $nameservers = [], array $extra = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Spaceship is not configured'];
        }
        $body = [
            'years' => $years,
            'autoRenew' => (bool) ($extra['auto_renew'] ?? false),
            'privacyProtection' => [
                'level' => (string) ($extra['privacy'] ?? 'high'),
                'userConsent' => true,
            ],
            'contacts' => $this->mapContacts($contacts),
        ];
        if ($nameservers) {
            $body['nameservers'] = array_values(array_map('strval', $nameservers));
        }
        $resp = $this->http->post(
            $this->baseUrl() . '/v1/domains/' . rawurlencode($domain),
            $body,
            $this->headers(),
        );
        return $this->resolveOperation($resp, ['registrar_id' => $domain]);
    }

    public function renew(string $domain, int $years): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Spaceship is not configured'];
        }
        $resp = $this->http->post(
            $this->baseUrl() . '/v1/domains/' . rawurlencode($domain) . '/renew',
            ['years' => $years],
            $this->headers(),
        );
        return $this->resolveOperation($resp);
    }

    public function transfer(string $domain, string $authCode, array $contacts): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Spaceship is not configured'];
        }
        $body = [
            'authCode' => $authCode,
            'privacyProtection' => [
                'level' => 'high',
                'userConsent' => true,
            ],
        ];
        if ($contacts) {
            $body['contacts'] = $this->mapContacts($contacts);
        }
        $resp = $this->http->post(
            $this->baseUrl() . '/v1/domains/' . rawurlencode($domain) . '/transfer',
            $body,
            $this->headers(),
        );
        $out = $this->resolveOperation($resp);
        if (!empty($out['operation_id']) && empty($out['transfer_id'])) {
            $out['transfer_id'] = $out['operation_id'];
        }
        return $out;
    }

    public function getNameservers(string $domain): array
    {
        if (!$this->isConfigured()) {
            return [];
        }
        $resp = $this->http->get(
            $this->baseUrl() . '/v1/domains/' . rawurlencode($domain),
            $this->headers(),
        );
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json)) {
            return [];
        }
        $ns = $json['nameservers'] ?? ($json['nameServers'] ?? []);
        if (is_array($ns) && isset($ns['hosts'])) {
            $ns = $ns['hosts'];
        }
        return is_array($ns) ? array_values(array_map('strval', $ns)) : [];
    }

    public function setNameservers(string $domain, array $hosts): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }
        $resp = $this->http->put(
            $this->baseUrl() . '/v1/domains/' . rawurlencode($domain) . '/nameservers',
            ['nameservers' => array_values(array_map('strval', $hosts))],
            $this->headers(),
        );
        return $this->isAccepted($resp);
    }

    public function getEppCode(string $domain): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }
        $resp = $this->http->get(
            $this->baseUrl() . '/v1/domains/' . rawurlencode($domain) . '/transfer/auth-code',
            $this->headers(),
        );
        if (!$resp->ok()) {
            return null;
        }
        $json = $resp->json();
        if (!is_array($json)) {
            return null;
        }
        $code = $json['authCode'] ?? $json['code'] ?? null;
        return $code !== null && $code !== '' ? (string) $code : null;
    }

    public function setLock(string $domain, bool $locked): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }
        $resp = $this->http->put(
            $this->baseUrl() . '/v1/domains/' . rawurlencode($domain) . '/transfer/lock',
            ['isLocked' => $locked],
            $this->headers(),
        );
        return $this->isAccepted($resp);
    }

    public function setPrivacy(string $domain, bool $enabled): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }
        $resp = $this->http->put(
            $this->baseUrl() . '/v1/domains/' . rawurlencode($domain) . '/privacy',
            [
                'level' => $enabled ? 'high' : 'public',
                'userConsent' => true,
            ],
            $this->headers(),
        );
        return $this->isAccepted($resp);
    }

    public function setAutoRenew(string $domain, bool $enabled): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }
        $resp = $this->http->put(
            $this->baseUrl() . '/v1/domains/' . rawurlencode($domain) . '/autorenew',
            ['isAutorenew' => $enabled],
            $this->headers(),
        );
        return $this->isAccepted($resp);
    }

    public function listDnsRecords(string $domain): array
    {
        if (!$this->isConfigured()) {
            return [];
        }
        $records = [];
        $skip = 0;
        $take = max(1, (int) ($this->cfg['page_size'] ?? self::DEFAULT_PAGE_SIZE));
        // Hard cap to prevent infinite loops on misbehaving APIs.
        for ($i = 0; $i < 100; $i++) {
            $resp = $this->http->get(
                $this->baseUrl() . '/v1/dns/records/' . rawurlencode($domain),
                $this->headers(),
                ['take' => $take, 'skip' => $skip, 'orderBy' => 'name'],
            );
            $json = $resp->json();
            if (!$resp->ok() || !is_array($json)) {
                break;
            }
            $items = $json['items'] ?? [];
            if (!is_array($items) || $items === []) {
                break;
            }
            foreach ($items as $r) {
                if (is_array($r)) {
                    $records[] = $this->normalizeRecord($r);
                }
            }
            // Partial page = last page.
            if (count($items) < $take) {
                break;
            }
            $skip += $take;
        }
        return $records;
    }

    /**
     * Replace-all: delete every existing record, then PUT the new set.
     */
    public function setDnsRecords(string $domain, array $records): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }
        $existing = $this->listDnsRecords($domain);
        if ($existing) {
            $delItems = [];
            foreach ($existing as $r) {
                $delItems[] = $this->denormalizeForDelete($r);
            }
            // DELETE-with-body — `Client::delete()` doesn't accept a body, so
            // route through `request()` directly.
            $this->http->request(
                'DELETE',
                $this->baseUrl() . '/v1/dns/records/' . rawurlencode($domain),
                ['items' => $delItems],
                $this->headers(),
            );
        }
        if ($records === []) {
            return true;
        }
        $items = [];
        foreach ($records as $r) {
            $items[] = $this->denormalizeForCreate($r);
        }
        $resp = $this->http->put(
            $this->baseUrl() . '/v1/dns/records/' . rawurlencode($domain),
            ['force' => true, 'items' => $items],
            $this->headers(),
        );
        return $resp->ok();
    }

    /**
     * Poll an async operation by id (returned in the `spaceship-async-operationid`
     * header on 202 responses).
     *
     * @return array{status:string,type?:string,details?:mixed,error?:string}
     */
    public function fetchOperation(string $operationId): array
    {
        if (!$this->isConfigured()) {
            return ['status' => 'failed', 'error' => 'Spaceship is not configured'];
        }
        $resp = $this->http->get(
            $this->baseUrl() . '/v1/async-operations/' . rawurlencode($operationId),
            $this->headers(),
        );
        $json = $resp->json();
        if (!$resp->ok() || !is_array($json)) {
            return ['status' => 'failed', 'error' => $this->errorMessage($json) ?? ('Spaceship HTTP ' . $resp->status)];
        }
        return [
            'status' => (string) ($json['status'] ?? 'pending'),
            'type' => isset($json['type']) ? (string) $json['type'] : null,
            'details' => $json['details'] ?? null,
        ];
    }

    public function isConfigured(): bool
    {
        return !empty($this->cfg['api_key']) && !empty($this->cfg['api_secret']);
    }

    public function baseUrl(): string
    {
        $override = trim((string) ($this->cfg['base_url'] ?? ''));
        return $override !== '' ? rtrim($override, '/') : self::API_BASE;
    }

    /**
     * @return array<string,string>
     */
    private function headers(): array
    {
        return [
            'X-Api-Key' => (string) ($this->cfg['api_key'] ?? ''),
            'X-Api-Secret' => (string) ($this->cfg['api_secret'] ?? ''),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Accept either a flat contact array (used for all 4 roles) or a per-role
     * map keyed by `registrant`/`admin`/`tech`/`billing`.
     *
     * @param array<string,mixed> $contacts
     * @return array<string,mixed>
     */
    private function mapContacts(array $contacts): array
    {
        $roles = ['registrant', 'admin', 'tech', 'billing'];
        $present = 0;
        foreach ($roles as $role) {
            if (isset($contacts[$role]) && is_array($contacts[$role])) {
                $present++;
            }
        }
        if ($present === count($roles)) {
            return [
                'registrant' => $contacts['registrant'],
                'admin' => $contacts['admin'],
                'tech' => $contacts['tech'],
                'billing' => $contacts['billing'],
            ];
        }
        return [
            'registrant' => $contacts,
            'admin' => $contacts,
            'tech' => $contacts,
            'billing' => $contacts,
        ];
    }

    /**
     * @param array<string,mixed> $r
     * @return array{type:string,name:string,content:string,ttl:int,priority:?int}
     */
    private function normalizeRecord(array $r): array
    {
        $type = strtoupper((string) ($r['type'] ?? ''));
        $content = match (true) {
            isset($r['address']) => (string) $r['address'],
            isset($r['cname']) => (string) $r['cname'],
            isset($r['target']) => (string) $r['target'],
            isset($r['exchange']) => (string) $r['exchange'],
            isset($r['nameserver']) => (string) $r['nameserver'],
            isset($r['value']) => (string) $r['value'],
            isset($r['data']) => (string) $r['data'],
            default => '',
        };
        $priority = null;
        foreach (['preference', 'priority'] as $k) {
            if (isset($r[$k])) {
                $priority = (int) $r[$k];
                break;
            }
        }
        return [
            'type' => $type,
            'name' => (string) ($r['name'] ?? ''),
            'content' => $content,
            'ttl' => (int) ($r['ttl'] ?? 3600),
            'priority' => $priority,
        ];
    }

    /**
     * @param array<string,mixed> $r
     * @return array<string,mixed>
     */
    private function denormalizeForCreate(array $r): array
    {
        $type = strtoupper((string) ($r['type'] ?? 'A'));
        $out = [
            'type' => $type,
            'name' => (string) ($r['name'] ?? '@'),
            'ttl' => (int) ($r['ttl'] ?? 3600),
        ];
        $content = (string) ($r['content'] ?? '');
        $this->fillContent($out, $type, $content);
        if (isset($r['priority'])) {
            $out[$type === 'MX' ? 'preference' : 'priority'] = (int) $r['priority'];
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $r
     * @return array<string,mixed>
     */
    private function denormalizeForDelete(array $r): array
    {
        $type = strtoupper((string) ($r['type'] ?? 'A'));
        $out = ['type' => $type, 'name' => (string) ($r['name'] ?? '@')];
        $content = (string) ($r['content'] ?? '');
        $this->fillContent($out, $type, $content);
        return $out;
    }

    /**
     * @param array<string,mixed> $body
     */
    private function fillContent(array &$body, string $type, string $content): void
    {
        switch ($type) {
            case 'A':
            case 'AAAA':
                $body['address'] = $content;
                return;
            case 'CNAME':
                $body['cname'] = $content;
                return;
            case 'MX':
                $body['exchange'] = $content;
                return;
            case 'NS':
                $body['nameserver'] = $content;
                return;
            case 'TXT':
            case 'SPF':
                $body['value'] = $content;
                return;
            default:
                $body['value'] = $content;
        }
    }

    /**
     * Both 2xx and 202-with-operation-id are considered "accepted".
     */
    private function isAccepted(Response $resp): bool
    {
        if ($resp->ok()) {
            return true;
        }
        if ($resp->status === 202) {
            return true;
        }
        return false;
    }

    /**
     * Map a register / renew / transfer response into the shape the
     * RegistrarInterface contract expects.
     *
     * @param array<string,mixed> $extras
     * @return array<string,mixed>
     */
    private function resolveOperation(Response $resp, array $extras = []): array
    {
        // 202 is technically a 2xx, but it means "accepted, async pending".
        if ($resp->status === 202) {
            $opId = (string) ($resp->headers['spaceship-async-operationid'] ?? '');
            return [
                'success' => true,
                'pending' => true,
                'operation_id' => $opId,
            ] + $extras;
        }
        if ($resp->ok()) {
            $out = ['success' => true] + $extras;
            $json = $resp->json();
            if (is_array($json)) {
                if (isset($json['expiresAt'])) {
                    $out['expires_at'] = (string) $json['expiresAt'];
                } elseif (isset($json['expirationDate'])) {
                    $out['expires_at'] = (string) $json['expirationDate'];
                }
            }
            return $out;
        }
        return [
            'success' => false,
            'error' => $this->errorMessage($resp->json()) ?? ('Spaceship HTTP ' . $resp->status),
        ];
    }

    /**
     * @param mixed $json
     */
    private function errorMessage($json): ?string
    {
        if (!is_array($json)) {
            return null;
        }
        if (isset($json['detail'])) {
            return (string) $json['detail'];
        }
        if (isset($json['title'])) {
            return (string) $json['title'];
        }
        if (isset($json['message'])) {
            return (string) $json['message'];
        }
        if (isset($json['errors']) && is_array($json['errors'])) {
            $first = $json['errors'][0] ?? null;
            if (is_array($first)) {
                if (isset($first['message'])) {
                    return (string) $first['message'];
                }
                if (isset($first['detail'])) {
                    return (string) $first['detail'];
                }
            }
            if (is_string($first)) {
                return $first;
            }
        }
        return null;
    }
}
