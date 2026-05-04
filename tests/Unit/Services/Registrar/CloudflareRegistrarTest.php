<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Registrar;

use App\Services\Registrar\Drivers\CloudflareRegistrar;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryHttpClient;

final class CloudflareRegistrarTest extends TestCase
{
    public function test_unconfigured_driver_returns_no_results(): void
    {
        $r = new CloudflareRegistrar([], new InMemoryHttpClient());
        $this->assertSame([], $r->getNameservers('foo.com'));
        $this->assertSame([], $r->listDnsRecords('foo.com'));
        $this->assertFalse($r->setLock('foo.com', true));
    }

    public function test_register_and_renew_and_transfer_are_unsupported(): void
    {
        $r = new CloudflareRegistrar($this->cfg(), new InMemoryHttpClient());
        foreach (['register', 'renew', 'transfer'] as $method) {
            $args = match ($method) {
                'register' => ['foo.com', 1, []],
                'renew' => ['foo.com', 1],
                'transfer' => ['foo.com', 'auth-code', []],
            };
            $result = $r->{$method}(...$args);
            $this->assertFalse($result['success']);
            $this->assertStringContainsString('does not support', $result['error']);
        }
        $this->assertNull($r->getEppCode('foo.com'));
        $this->assertFalse($r->setNameservers('foo.com', ['ns1', 'ns2']));
    }

    public function test_resolve_zone_caches_lookup(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'success' => true,
            'result' => [['id' => 'ZONEID', 'name' => 'foo.com']],
        ]));
        $r = new CloudflareRegistrar($this->cfg(), $http);

        $this->assertSame('ZONEID', $r->resolveZone('foo.com'));
        $this->assertSame('ZONEID', $r->resolveZone('foo.com'));
        $this->assertCount(1, $http->requests, 'zone lookup should be cached');

        $req = $http->lastRequest();
        $this->assertStringContainsString('https://api.cloudflare.com/client/v4/zones', $req['url']);
        $this->assertStringContainsString('name=foo.com', $req['url']);
        $this->assertSame('Bearer cf-token', $req['headers']['Authorization']);
    }

    public function test_get_nameservers_reads_zone_details(): void
    {
        $http = new InMemoryHttpClient([
            InMemoryHttpClient::jsonResponse(200, ['success' => true, 'result' => [['id' => 'Z']]]),
            InMemoryHttpClient::jsonResponse(200, ['success' => true, 'result' => ['name_servers' => ['ali.ns.cloudflare.com', 'tom.ns.cloudflare.com']]]),
        ]);
        $r = new CloudflareRegistrar($this->cfg(), $http);

        $this->assertSame(
            ['ali.ns.cloudflare.com', 'tom.ns.cloudflare.com'],
            $r->getNameservers('foo.com'),
        );
    }

    public function test_list_dns_records_paginates(): void
    {
        $http = new InMemoryHttpClient([
            InMemoryHttpClient::jsonResponse(200, ['success' => true, 'result' => [['id' => 'Z']]]),
            InMemoryHttpClient::jsonResponse(200, [
                'success' => true,
                'result' => [['id' => 'r1', 'type' => 'A', 'name' => 'foo.com', 'content' => '1.1.1.1', 'ttl' => 300]],
                'result_info' => ['total_pages' => 2],
            ]),
            InMemoryHttpClient::jsonResponse(200, [
                'success' => true,
                'result' => [['id' => 'r2', 'type' => 'CNAME', 'name' => 'www.foo.com', 'content' => 'foo.com', 'ttl' => 1]],
                'result_info' => ['total_pages' => 2],
            ]),
        ]);
        $r = new CloudflareRegistrar($this->cfg(), $http);

        $records = $r->listDnsRecords('foo.com');
        $this->assertCount(2, $records);
        $this->assertSame('A', $records[0]['type']);
        $this->assertSame('CNAME', $records[1]['type']);

        $this->assertStringContainsString('page=1', $http->requests[1]['url']);
        $this->assertStringContainsString('page=2', $http->requests[2]['url']);
    }

    public function test_set_dns_records_replaces_all(): void
    {
        $http = new InMemoryHttpClient(null, function (array $req) {
            // 1) zones?name=foo.com → returns zone id (called for resolveZone, then listDnsRecords reuses cache, then resolveZone again — actually resolveZone caches across calls)
            if (str_starts_with($req['url'], 'https://api.cloudflare.com/client/v4/zones?name=')) {
                return InMemoryHttpClient::jsonResponse(200, ['success' => true, 'result' => [['id' => 'Z']]]);
            }
            // 2) GET dns_records (during listDnsRecords)
            if ($req['method'] === 'GET' && str_contains($req['url'], '/zones/Z/dns_records')) {
                return InMemoryHttpClient::jsonResponse(200, [
                    'success' => true,
                    'result' => [['id' => 'old1', 'type' => 'A', 'name' => 'foo.com', 'content' => '0.0.0.0']],
                    'result_info' => ['total_pages' => 1],
                ]);
            }
            // 3) DELETE old records
            if ($req['method'] === 'DELETE') {
                return InMemoryHttpClient::jsonResponse(200, ['success' => true]);
            }
            // 4) POST new records
            return InMemoryHttpClient::jsonResponse(200, ['success' => true, 'result' => ['id' => 'new1']]);
        });
        $r = new CloudflareRegistrar($this->cfg(), $http);

        $this->assertTrue($r->setDnsRecords('foo.com', [
            ['type' => 'A', 'name' => 'foo.com', 'content' => '1.2.3.4', 'ttl' => 300, 'proxied' => true],
            ['type' => 'TXT', 'name' => 'foo.com', 'content' => 'hello'],
        ]));

        $methods = array_column($http->requests, 'method');
        $this->assertContains('DELETE', $methods);
        $this->assertContains('POST', $methods);

        $posts = array_filter($http->requests, fn ($r) => $r['method'] === 'POST');
        $this->assertCount(2, $posts, 'expected 2 record creations');

        $firstPostBody = array_values($posts)[0]['body'];
        $this->assertSame('A', $firstPostBody['type']);
        $this->assertSame('1.2.3.4', $firstPostBody['content']);
        $this->assertTrue($firstPostBody['proxied']);
    }

    public function test_set_lock_calls_registrar_endpoint(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, ['success' => true]));
        $r = new CloudflareRegistrar($this->cfg(['account_id' => 'ACC-1']), $http);

        $this->assertTrue($r->setLock('foo.com', true));
        $req = $http->lastRequest();
        $this->assertSame('PUT', $req['method']);
        $this->assertSame('https://api.cloudflare.com/client/v4/accounts/ACC-1/registrar/domains/foo.com', $req['url']);
        $this->assertTrue($req['body']['locked']);
    }

    public function test_set_lock_without_account_id_returns_false(): void
    {
        $r = new CloudflareRegistrar($this->cfg(['account_id' => '']), new InMemoryHttpClient());
        $this->assertFalse($r->setLock('foo.com', true));
    }

    public function test_check_availability_returns_available_on_404(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(404, [
            'success' => false,
            'errors' => [['code' => 1003, 'message' => 'Domain not found']],
        ]));
        $r = new CloudflareRegistrar($this->cfg(['account_id' => 'ACC-1']), $http);

        $result = $r->checkAvailability('not-registered.com');
        $this->assertTrue($result['available']);
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private function cfg(array $overrides = []): array
    {
        return array_merge([
            'api_token' => 'cf-token',
            'account_id' => 'ACC-1',
        ], $overrides);
    }
}
