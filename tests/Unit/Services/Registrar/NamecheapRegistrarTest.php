<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Registrar;

use App\Core\Http\Response;
use App\Services\Registrar\Drivers\NamecheapRegistrar;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryHttpClient;

final class NamecheapRegistrarTest extends TestCase
{
    public function test_unconfigured_check_returns_error(): void
    {
        $r = new NamecheapRegistrar([], new InMemoryHttpClient());
        $result = $r->checkAvailability('example.com');
        $this->assertFalse($result['available']);
        $this->assertSame('Namecheap API call failed', $result['error']);
    }

    public function test_check_availability_parses_xml(): void
    {
        $xml = $this->wrap('<CommandResponse Type="namecheap.domains.check">'
            . '<DomainCheckResult Domain="foo.com" Available="true" IsPremiumName="false" PremiumRegistrationPrice="0.00"/>'
            . '</CommandResponse>');
        $http = new InMemoryHttpClient(new Response(200, [], $xml));
        $r = new NamecheapRegistrar($this->cfg(), $http);

        $result = $r->checkAvailability('foo.com');
        $this->assertTrue($result['available']);
        $this->assertFalse($result['premium']);
        $this->assertSame('USD', $result['currency']);

        $req = $http->lastRequest();
        $this->assertSame('GET', $req['method']);
        $this->assertStringContainsString('Command=namecheap.domains.check', $req['url']);
        $this->assertStringContainsString('DomainList=foo.com', $req['url']);
        $this->assertStringContainsString('ApiUser=test-user', $req['url']);
        $this->assertStringContainsString('ApiKey=test-key', $req['url']);
    }

    public function test_check_availability_returns_premium_metadata(): void
    {
        $xml = $this->wrap('<CommandResponse Type="namecheap.domains.check">'
            . '<DomainCheckResult Domain="rare.com" Available="false" IsPremiumName="true" PremiumRegistrationPrice="2500.00"/>'
            . '</CommandResponse>');
        $http = new InMemoryHttpClient(new Response(200, [], $xml));
        $r = new NamecheapRegistrar($this->cfg(), $http);

        $result = $r->checkAvailability('rare.com');
        $this->assertFalse($result['available']);
        $this->assertTrue($result['premium']);
        $this->assertSame(2500.00, $result['price']);
    }

    public function test_register_sends_contacts_for_each_role(): void
    {
        $xml = $this->wrap('<CommandResponse Type="namecheap.domains.create">'
            . '<DomainCreateResult Domain="foo.com" Registered="true" DomainID="12345" DomainExpiry="01/02/2027"/>'
            . '</CommandResponse>');
        $http = new InMemoryHttpClient(new Response(200, [], $xml));
        $r = new NamecheapRegistrar($this->cfg(), $http);

        $contacts = [
            'first_name' => 'Alex',
            'last_name' => 'Bruno',
            'email' => 'alex@example.com',
            'address1' => '1 Main St',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postal_code' => '1212',
            'country' => 'BD',
            'phone' => '+880.1700000000',
        ];
        $result = $r->register('foo.com', 2, $contacts, ['ns1.cf.com', 'ns2.cf.com']);

        $this->assertTrue($result['success']);
        $this->assertSame('12345', $result['registrar_id']);
        $this->assertNotEmpty($result['expires_at']);

        $url = $http->lastRequest()['url'];
        $this->assertStringContainsString('Command=namecheap.domains.create', $url);
        $this->assertStringContainsString('Years=2', $url);
        $this->assertStringContainsString('Nameservers=ns1.cf.com%2Cns2.cf.com', $url);
        // All four contact roles must be present.
        foreach (['Registrant', 'Tech', 'Admin', 'AuxBilling'] as $role) {
            $this->assertStringContainsString("{$role}FirstName=Alex", $url);
            $this->assertStringContainsString("{$role}EmailAddress=alex%40example.com", $url);
        }
    }

    public function test_register_surfaces_errors_array(): void
    {
        $xml = $this->wrap('<Errors><Error Number="2030280">TLD is not supported</Error></Errors>'
            . '<CommandResponse Type="namecheap.domains.create"/>', status: 'ERROR');
        $http = new InMemoryHttpClient(new Response(200, [], $xml));
        $r = new NamecheapRegistrar($this->cfg(), $http);

        $result = $r->register('foo.invalid', 1, ['first_name' => 'A', 'last_name' => 'B']);
        $this->assertFalse($result['success']);
        $this->assertSame('TLD is not supported', $result['error']);
    }

    public function test_renew_parses_renewal_response(): void
    {
        $xml = $this->wrap('<CommandResponse Type="namecheap.domains.renew">'
            . '<DomainRenewResult DomainID="1" DomainName="foo.com" Renew="true">'
            . '<DomainDetails><ExpiredDate>06/15/2030</ExpiredDate></DomainDetails>'
            . '</DomainRenewResult>'
            . '</CommandResponse>');
        $http = new InMemoryHttpClient(new Response(200, [], $xml));
        $r = new NamecheapRegistrar($this->cfg(), $http);

        $result = $r->renew('foo.com', 1);
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['expires_at']);
    }

    public function test_set_nameservers_uses_set_default_when_list_empty(): void
    {
        $xml = $this->wrap('<CommandResponse Type="namecheap.domains.dns.setDefault">'
            . '<DomainDNSSetDefaultResult Domain="foo.com" Updated="true"/>'
            . '</CommandResponse>');
        $http = new InMemoryHttpClient(new Response(200, [], $xml));
        $r = new NamecheapRegistrar($this->cfg(), $http);

        $this->assertTrue($r->setNameservers('foo.com', []));
        $this->assertStringContainsString('Command=namecheap.domains.dns.setDefault', $http->lastRequest()['url']);
    }

    public function test_set_nameservers_uses_set_custom_when_list_provided(): void
    {
        $xml = $this->wrap('<CommandResponse Type="namecheap.domains.dns.setCustom">'
            . '<DomainDNSSetCustomResult Domain="foo.com" Updated="true"/>'
            . '</CommandResponse>');
        $http = new InMemoryHttpClient(new Response(200, [], $xml));
        $r = new NamecheapRegistrar($this->cfg(), $http);

        $this->assertTrue($r->setNameservers('foo.com', ['ns1.cf.com', 'ns2.cf.com']));
        $url = $http->lastRequest()['url'];
        $this->assertStringContainsString('Command=namecheap.domains.dns.setCustom', $url);
        $this->assertStringContainsString('Nameservers=ns1.cf.com%2Cns2.cf.com', $url);
        $this->assertStringContainsString('SLD=foo', $url);
        $this->assertStringContainsString('TLD=com', $url);
    }

    public function test_set_lock_sends_lock_unlock_action(): void
    {
        $xml = $this->wrap('<CommandResponse Type="namecheap.domains.setRegistrarLock">'
            . '<DomainSetRegistrarLockResult Domain="foo.com" IsSuccess="true"/>'
            . '</CommandResponse>');
        $http = new InMemoryHttpClient([new Response(200, [], $xml), new Response(200, [], $xml)]);
        $r = new NamecheapRegistrar($this->cfg(), $http);

        $this->assertTrue($r->setLock('foo.com', true));
        $this->assertTrue($r->setLock('foo.com', false));

        $this->assertStringContainsString('LockAction=LOCK', $http->requests[0]['url']);
        $this->assertStringContainsString('LockAction=UNLOCK', $http->requests[1]['url']);
    }

    public function test_list_dns_records_parses_hosts(): void
    {
        $xml = $this->wrap('<CommandResponse Type="namecheap.domains.dns.getHosts">'
            . '<DomainDNSGetHostsResult Domain="foo.com" IsUsingOurDNS="true">'
            . '<host HostId="1" Name="@" Type="A" Address="1.2.3.4" MXPref="10" TTL="1800"/>'
            . '<host HostId="2" Name="www" Type="CNAME" Address="@" MXPref="10" TTL="3600"/>'
            . '</DomainDNSGetHostsResult></CommandResponse>');
        $http = new InMemoryHttpClient(new Response(200, [], $xml));
        $r = new NamecheapRegistrar($this->cfg(), $http);

        $records = $r->listDnsRecords('foo.com');
        $this->assertCount(2, $records);
        $this->assertSame('A', $records[0]['type']);
        $this->assertSame('1.2.3.4', $records[0]['content']);
        $this->assertSame(1800, $records[0]['ttl']);
        $this->assertSame('CNAME', $records[1]['type']);
    }

    public function test_set_dns_records_serializes_indexed_params(): void
    {
        $xml = $this->wrap('<CommandResponse Type="namecheap.domains.dns.setHosts">'
            . '<DomainDNSSetHostsResult Domain="foo.com" IsSuccess="true"/>'
            . '</CommandResponse>');
        $http = new InMemoryHttpClient(new Response(200, [], $xml));
        $r = new NamecheapRegistrar($this->cfg(), $http);

        $records = [
            ['name' => '@', 'type' => 'A', 'content' => '1.2.3.4', 'ttl' => 1800],
            ['name' => 'www', 'type' => 'CNAME', 'content' => '@', 'ttl' => 3600],
        ];
        $this->assertTrue($r->setDnsRecords('foo.com', $records));

        $url = $http->lastRequest()['url'];
        $this->assertStringContainsString('HostName1=%40', $url);
        $this->assertStringContainsString('RecordType1=A', $url);
        $this->assertStringContainsString('Address1=1.2.3.4', $url);
        $this->assertStringContainsString('HostName2=www', $url);
        $this->assertStringContainsString('RecordType2=CNAME', $url);
    }

    public function test_set_auto_renew_returns_false_unsupported(): void
    {
        $r = new NamecheapRegistrar($this->cfg(), new InMemoryHttpClient());
        $this->assertFalse($r->setAutoRenew('foo.com', true));
    }

    public function test_get_epp_code_returns_null(): void
    {
        $r = new NamecheapRegistrar($this->cfg(), new InMemoryHttpClient());
        $this->assertNull($r->getEppCode('foo.com'));
    }

    public function test_split_domain(): void
    {
        $this->assertSame(['foo', 'com'], NamecheapRegistrar::splitDomain('foo.com'));
        // explode('.', $d, 2) keeps the rest of the dotted name intact in TLD.
        $this->assertSame(['sub', 'foo.com'], NamecheapRegistrar::splitDomain('sub.foo.com'));
        $this->assertSame(['singleword', ''], NamecheapRegistrar::splitDomain('singleword'));
    }

    public function test_base_url_switches_on_sandbox(): void
    {
        $sb = new NamecheapRegistrar($this->cfg(['sandbox' => true]));
        $live = new NamecheapRegistrar($this->cfg(['sandbox' => false]));
        $this->assertStringStartsWith('https://api.sandbox.namecheap.com', $sb->baseUrl());
        $this->assertStringStartsWith('https://api.namecheap.com', $live->baseUrl());
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private function cfg(array $overrides = []): array
    {
        return array_merge([
            'api_user' => 'test-user',
            'api_key' => 'test-key',
            'username' => 'test-user',
            'client_ip' => '127.0.0.1',
            'sandbox' => true,
        ], $overrides);
    }

    private function wrap(string $inner, string $status = 'OK'): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<ApiResponse Status="' . $status . '" xmlns="http://api.namecheap.com/xml.response">'
            . '<RequestedCommand>command</RequestedCommand>'
            . $inner
            . '<Server>SERVER</Server><GMTTimeDifference>+0</GMTTimeDifference><ExecutionTime>0.01</ExecutionTime>'
            . '</ApiResponse>';
    }
}
