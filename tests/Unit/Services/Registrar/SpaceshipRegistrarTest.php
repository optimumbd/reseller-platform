<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Registrar;

use App\Core\Http\Response;
use App\Services\Registrar\Drivers\SpaceshipRegistrar;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryHttpClient;

final class SpaceshipRegistrarTest extends TestCase
{
    public function test_unconfigured_driver_short_circuits(): void
    {
        $r = new SpaceshipRegistrar([], new InMemoryHttpClient());
        $this->assertSame([], $r->getNameservers('foo.com'));
        $this->assertSame([], $r->listDnsRecords('foo.com'));
        $this->assertFalse($r->setLock('foo.com', true));
        $this->assertFalse($r->setPrivacy('foo.com', true));
        $this->assertFalse($r->setAutoRenew('foo.com', true));
        $this->assertFalse($r->setNameservers('foo.com', ['ns1', 'ns2']));
        $this->assertNull($r->getEppCode('foo.com'));

        $check = $r->checkAvailability('foo.com');
        $this->assertFalse($check['available']);
        $this->assertSame('Spaceship is not configured', $check['error']);

        $reg = $r->register('foo.com', 1, []);
        $this->assertFalse($reg['success']);
        $this->assertSame('Spaceship is not configured', $reg['error']);
    }

    public function test_check_availability_normalizes_payload(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'isAvailable' => true,
            'isPremium' => true,
            'price' => 12.99,
            'currency' => 'USD',
        ]));
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $out = $r->checkAvailability('foo.com');
        $this->assertSame([
            'available' => true,
            'premium' => true,
            'price' => 12.99,
            'currency' => 'USD',
        ], $out);

        $req = $http->lastRequest();
        $this->assertSame('GET', $req['method']);
        $this->assertSame('https://spaceship.dev/api/v1/domains/foo.com/availability', $req['url']);
        $this->assertSame('k', $req['headers']['X-Api-Key']);
        $this->assertSame('s', $req['headers']['X-Api-Secret']);
    }

    public function test_check_availability_surfaces_api_error(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(400, [
            'detail' => 'Domain not allowed',
        ]));
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $out = $r->checkAvailability('foo.com');
        $this->assertFalse($out['available']);
        $this->assertSame('Domain not allowed', $out['error']);
    }

    public function test_register_sends_full_payload_and_handles_async_response(): void
    {
        $http = new InMemoryHttpClient(new Response(202, [
            'spaceship-async-operationid' => 'op-abc',
        ], ''));
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $contacts = [
            'firstName' => 'Alex',
            'lastName' => 'Bruno',
            'email' => 'alex@example.com',
            'country' => 'BD',
        ];
        $out = $r->register('foo.com', 2, $contacts, ['ns1.cloudflare.com', 'ns2.cloudflare.com'], ['privacy' => 'high', 'auto_renew' => true]);

        $this->assertTrue($out['success']);
        $this->assertTrue($out['pending']);
        $this->assertSame('op-abc', $out['operation_id']);
        $this->assertSame('foo.com', $out['registrar_id']);

        $req = $http->lastRequest();
        $this->assertSame('POST', $req['method']);
        $this->assertSame('https://spaceship.dev/api/v1/domains/foo.com', $req['url']);

        $body = $req['body'];
        $this->assertSame(2, $body['years']);
        $this->assertTrue($body['autoRenew']);
        $this->assertSame('high', $body['privacyProtection']['level']);
        $this->assertTrue($body['privacyProtection']['userConsent']);
        // Contacts are mirrored to all four roles when caller passed a flat contact.
        foreach (['registrant', 'admin', 'tech', 'billing'] as $role) {
            $this->assertSame($contacts, $body['contacts'][$role], "role $role");
        }
        $this->assertSame(['ns1.cloudflare.com', 'ns2.cloudflare.com'], $body['nameservers']);
    }

    public function test_register_accepts_per_role_contacts(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, ['expiresAt' => '2027-01-01']));
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $perRole = [
            'registrant' => ['firstName' => 'Reg'],
            'admin' => ['firstName' => 'Adm'],
            'tech' => ['firstName' => 'Tec'],
            'billing' => ['firstName' => 'Bil'],
        ];
        $out = $r->register('foo.com', 1, $perRole);

        $this->assertTrue($out['success']);
        $this->assertSame('2027-01-01', $out['expires_at']);

        $body = $http->lastRequest()['body'];
        $this->assertSame(['firstName' => 'Reg'], $body['contacts']['registrant']);
        $this->assertSame(['firstName' => 'Adm'], $body['contacts']['admin']);
        $this->assertSame(['firstName' => 'Tec'], $body['contacts']['tech']);
        $this->assertSame(['firstName' => 'Bil'], $body['contacts']['billing']);
    }

    public function test_register_surfaces_api_error(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(409, [
            'errors' => [['message' => 'taken']],
        ]));
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $out = $r->register('foo.com', 1, []);
        $this->assertFalse($out['success']);
        $this->assertSame('taken', $out['error']);
    }

    public function test_renew_posts_to_renew_endpoint(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, ['expiresAt' => '2028-01-01']));
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $out = $r->renew('foo.com', 3);
        $this->assertTrue($out['success']);
        $this->assertSame('2028-01-01', $out['expires_at']);

        $req = $http->lastRequest();
        $this->assertSame('POST', $req['method']);
        $this->assertSame('https://spaceship.dev/api/v1/domains/foo.com/renew', $req['url']);
        $this->assertSame(['years' => 3], $req['body']);
    }

    public function test_transfer_returns_operation_id_as_transfer_id(): void
    {
        $http = new InMemoryHttpClient(new Response(202, [
            'spaceship-async-operationid' => 'tx-xyz',
        ], ''));
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $out = $r->transfer('foo.com', 'EPP-CODE', []);
        $this->assertTrue($out['success']);
        $this->assertTrue($out['pending']);
        $this->assertSame('tx-xyz', $out['operation_id']);
        $this->assertSame('tx-xyz', $out['transfer_id']);

        $req = $http->lastRequest();
        $this->assertSame('https://spaceship.dev/api/v1/domains/foo.com/transfer', $req['url']);
        $this->assertSame('EPP-CODE', $req['body']['authCode']);
    }

    public function test_get_nameservers_reads_domain_info(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'name' => 'foo.com',
            'nameservers' => ['a.ns.example', 'b.ns.example'],
        ]));
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $this->assertSame(['a.ns.example', 'b.ns.example'], $r->getNameservers('foo.com'));

        $req = $http->lastRequest();
        $this->assertSame('GET', $req['method']);
        $this->assertSame('https://spaceship.dev/api/v1/domains/foo.com', $req['url']);
    }

    public function test_get_nameservers_handles_nested_hosts_field(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'nameservers' => ['hosts' => ['ns1.foo', 'ns2.foo']],
        ]));
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $this->assertSame(['ns1.foo', 'ns2.foo'], $r->getNameservers('foo.com'));
    }

    public function test_set_nameservers_puts_to_nameservers_endpoint(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, []));
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $this->assertTrue($r->setNameservers('foo.com', ['ns1.foo', 'ns2.foo']));

        $req = $http->lastRequest();
        $this->assertSame('PUT', $req['method']);
        $this->assertSame('https://spaceship.dev/api/v1/domains/foo.com/nameservers', $req['url']);
        $this->assertSame(['nameservers' => ['ns1.foo', 'ns2.foo']], $req['body']);
    }

    public function test_set_nameservers_treats_202_as_accepted(): void
    {
        $http = new InMemoryHttpClient(new Response(202, ['spaceship-async-operationid' => 'op'], ''));
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $this->assertTrue($r->setNameservers('foo.com', ['ns1.foo']));
    }

    public function test_get_epp_code_returns_auth_code(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, ['authCode' => 'abc-123']));
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $this->assertSame('abc-123', $r->getEppCode('foo.com'));

        $req = $http->lastRequest();
        $this->assertSame('GET', $req['method']);
        $this->assertSame('https://spaceship.dev/api/v1/domains/foo.com/transfer/auth-code', $req['url']);
    }

    public function test_get_epp_code_returns_null_on_failure(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(404, ['detail' => 'not found']));
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $this->assertNull($r->getEppCode('foo.com'));
    }

    public function test_set_lock_puts_lock_state(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, []));
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $this->assertTrue($r->setLock('foo.com', true));
        $req = $http->lastRequest();
        $this->assertSame('PUT', $req['method']);
        $this->assertSame('https://spaceship.dev/api/v1/domains/foo.com/transfer/lock', $req['url']);
        $this->assertSame(['isLocked' => true], $req['body']);
    }

    public function test_set_privacy_translates_boolean_to_level(): void
    {
        $http = new InMemoryHttpClient([
            InMemoryHttpClient::jsonResponse(200, []),
            InMemoryHttpClient::jsonResponse(200, []),
        ]);
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $this->assertTrue($r->setPrivacy('foo.com', true));
        $this->assertSame(['level' => 'high', 'userConsent' => true], $http->requests[0]['body']);

        $this->assertTrue($r->setPrivacy('foo.com', false));
        $this->assertSame(['level' => 'public', 'userConsent' => true], $http->requests[1]['body']);
    }

    public function test_set_auto_renew_puts_state(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, []));
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $this->assertTrue($r->setAutoRenew('foo.com', true));
        $req = $http->lastRequest();
        $this->assertSame('https://spaceship.dev/api/v1/domains/foo.com/autorenew', $req['url']);
        $this->assertSame(['isAutorenew' => true], $req['body']);
    }

    public function test_list_dns_records_paginates_and_normalizes(): void
    {
        // page_size=2 forces pagination across 3 records.
        $page1 = InMemoryHttpClient::jsonResponse(200, [
            'items' => [
                ['type' => 'A', 'name' => 'foo.com', 'address' => '1.1.1.1', 'ttl' => 300],
                ['type' => 'CNAME', 'name' => 'www.foo.com', 'cname' => 'foo.com', 'ttl' => 600],
            ],
        ]);
        $page2 = InMemoryHttpClient::jsonResponse(200, [
            'items' => [
                ['type' => 'MX', 'name' => 'foo.com', 'exchange' => 'mx1.foo.com', 'preference' => 10, 'ttl' => 3600],
            ],
        ]);
        $http = new InMemoryHttpClient([$page1, $page2]);
        $r = new SpaceshipRegistrar($this->cfg() + ['page_size' => 2], $http);

        $records = $r->listDnsRecords('foo.com');
        $this->assertCount(3, $records);

        $this->assertSame('A', $records[0]['type']);
        $this->assertSame('1.1.1.1', $records[0]['content']);
        $this->assertSame(300, $records[0]['ttl']);

        $this->assertSame('CNAME', $records[1]['type']);
        $this->assertSame('foo.com', $records[1]['content']);

        $this->assertSame('MX', $records[2]['type']);
        $this->assertSame('mx1.foo.com', $records[2]['content']);
        $this->assertSame(10, $records[2]['priority']);

        // Pagination: skip=0 first, then skip=2 after pulling a full page.
        $this->assertStringContainsString('take=2', $http->requests[0]['url']);
        $this->assertStringContainsString('skip=0', $http->requests[0]['url']);
        $this->assertStringContainsString('skip=2', $http->requests[1]['url']);
    }

    public function test_set_dns_records_replaces_all(): void
    {
        $http = new InMemoryHttpClient(null, function (array $req) {
            // 1) GET dns/records (during listDnsRecords) — return 1 existing record.
            if ($req['method'] === 'GET' && str_contains($req['url'], '/dns/records/foo.com')) {
                return InMemoryHttpClient::jsonResponse(200, [
                    'items' => [
                        ['type' => 'A', 'name' => 'foo.com', 'address' => '0.0.0.0', 'ttl' => 300],
                    ],
                    'total' => 1,
                ]);
            }
            // 2) DELETE dns/records → 204 no content.
            if ($req['method'] === 'DELETE' && str_contains($req['url'], '/dns/records/foo.com')) {
                return new Response(204, [], '');
            }
            // 3) PUT dns/records → 200.
            if ($req['method'] === 'PUT' && str_contains($req['url'], '/dns/records/foo.com')) {
                return InMemoryHttpClient::jsonResponse(200, ['success' => true]);
            }
            return new Response(500, [], '');
        });

        $r = new SpaceshipRegistrar($this->cfg(), $http);
        $ok = $r->setDnsRecords('foo.com', [
            ['type' => 'A', 'name' => 'foo.com', 'content' => '1.1.1.1', 'ttl' => 300],
            ['type' => 'TXT', 'name' => 'foo.com', 'content' => 'v=spf1 -all', 'ttl' => 3600],
            ['type' => 'MX', 'name' => 'foo.com', 'content' => 'mx.foo.com', 'priority' => 10, 'ttl' => 3600],
        ]);
        $this->assertTrue($ok);

        // Verify the DELETE body included the existing record.
        $delete = null;
        $put = null;
        foreach ($http->requests as $req) {
            if ($req['method'] === 'DELETE' && str_contains($req['url'], '/dns/records/foo.com')) {
                $delete = $req;
            }
            if ($req['method'] === 'PUT' && str_contains($req['url'], '/dns/records/foo.com')) {
                $put = $req;
            }
        }
        $this->assertNotNull($delete, 'DELETE request was made');
        $this->assertSame([
            ['type' => 'A', 'name' => 'foo.com', 'address' => '0.0.0.0'],
        ], $delete['body']['items']);

        $this->assertNotNull($put, 'PUT request was made');
        $this->assertTrue($put['body']['force']);
        $this->assertSame([
            ['type' => 'A', 'name' => 'foo.com', 'ttl' => 300, 'address' => '1.1.1.1'],
            ['type' => 'TXT', 'name' => 'foo.com', 'ttl' => 3600, 'value' => 'v=spf1 -all'],
            ['type' => 'MX', 'name' => 'foo.com', 'ttl' => 3600, 'exchange' => 'mx.foo.com', 'preference' => 10],
        ], $put['body']['items']);
    }

    public function test_set_dns_records_with_empty_set_only_deletes(): void
    {
        $http = new InMemoryHttpClient(null, function (array $req) {
            if ($req['method'] === 'GET') {
                return InMemoryHttpClient::jsonResponse(200, [
                    'items' => [['type' => 'A', 'name' => 'foo.com', 'address' => '0.0.0.0']],
                    'total' => 1,
                ]);
            }
            if ($req['method'] === 'DELETE') {
                return new Response(204, [], '');
            }
            return new Response(500, [], '');
        });

        $r = new SpaceshipRegistrar($this->cfg(), $http);
        $this->assertTrue($r->setDnsRecords('foo.com', []));

        $methods = array_map(fn(array $req) => $req['method'], $http->requests);
        $this->assertNotContains('PUT', $methods, 'no PUT issued when records is empty');
        $this->assertContains('DELETE', $methods);
    }

    public function test_fetch_operation_returns_status(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, [
            'status' => 'success',
            'type' => 'domains_Create',
            'details' => ['domainName' => 'foo.com'],
        ]));
        $r = new SpaceshipRegistrar($this->cfg(), $http);

        $out = $r->fetchOperation('op-abc');
        $this->assertSame('success', $out['status']);
        $this->assertSame('domains_Create', $out['type']);

        $req = $http->lastRequest();
        $this->assertSame('GET', $req['method']);
        $this->assertSame('https://spaceship.dev/api/v1/async-operations/op-abc', $req['url']);
    }

    public function test_base_url_can_be_overridden(): void
    {
        $http = new InMemoryHttpClient(InMemoryHttpClient::jsonResponse(200, ['isAvailable' => true]));
        $r = new SpaceshipRegistrar(
            $this->cfg() + ['base_url' => 'https://staging.spaceship.dev/api/'],
            $http,
        );

        $r->checkAvailability('foo.com');
        $req = $http->lastRequest();
        $this->assertSame('https://staging.spaceship.dev/api/v1/domains/foo.com/availability', $req['url']);
    }

    /**
     * @return array<string,mixed>
     */
    private function cfg(): array
    {
        return ['api_key' => 'k', 'api_secret' => 's'];
    }
}
