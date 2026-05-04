<?php

declare(strict_types=1);

namespace App\Services\Registrar\Drivers;

use App\Core\Http\Client;
use App\Core\Http\HttpClientInterface;
use App\Core\Http\Response;
use App\Services\Registrar\Contracts\RegistrarInterface;

/**
 * Namecheap registrar driver — XML API at api.namecheap.com.
 *
 * Implements:
 *  - namecheap.domains.check
 *  - namecheap.domains.create
 *  - namecheap.domains.renew
 *  - namecheap.domains.transfer.create
 *  - namecheap.domains.dns.getList / setCustom / getDefault
 *  - namecheap.domains.dns.getHosts / setHosts
 *  - namecheap.domains.getRegistrarLock / setRegistrarLock
 *  - namecheap.whoisguard.* (privacy)
 *
 * Auto-renew and EPP retrieval are not exposed by Namecheap's public API and
 * always return a graceful "unsupported" outcome (false / null).
 */
final class NamecheapRegistrar implements RegistrarInterface
{
    private const SANDBOX_BASE = 'https://api.sandbox.namecheap.com/xml.response';
    private const PRODUCTION_BASE = 'https://api.namecheap.com/xml.response';

    /** @var array<string,mixed> */
    private array $cfg;
    private HttpClientInterface $http;

    public function __construct(?array $cfg = null, ?HttpClientInterface $http = null)
    {
        $this->cfg = $cfg ?? (array) (config('registrars.drivers.namecheap') ?? config('registrars.namecheap') ?? []);
        $this->http = $http ?? new Client();
    }

    public function baseUrl(): string
    {
        return !empty($this->cfg['sandbox']) ? self::SANDBOX_BASE : self::PRODUCTION_BASE;
    }

    public function checkAvailability(string $domain): array
    {
        $xml = $this->call('namecheap.domains.check', ['DomainList' => $domain]);
        if ($xml === null) {
            return ['available' => false, 'error' => 'Namecheap API call failed'];
        }
        $node = $xml->CommandResponse->DomainCheckResult ?? null;
        if ($node === null) {
            return ['available' => false, 'error' => 'No DomainCheckResult'];
        }
        $available = self::asBool((string) ($node['Available'] ?? 'false'));
        $premium = self::asBool((string) ($node['IsPremiumName'] ?? 'false'));
        $price = (float) ($node['PremiumRegistrationPrice'] ?? 0);
        return [
            'available' => $available,
            'premium' => $premium,
            'price' => $price,
            'currency' => 'USD',
        ];
    }

    public function register(string $domain, int $years, array $contacts, array $nameservers = [], array $extra = []): array
    {
        $params = [
            'DomainName' => $domain,
            'Years' => (string) max(1, $years),
        ];
        // Namecheap requires Registrant/Tech/Admin/AuxBilling contact sets.
        foreach (['Registrant', 'Tech', 'Admin', 'AuxBilling'] as $role) {
            $src = $contacts[$role] ?? $contacts['registrant'] ?? $contacts;
            $params += $this->mapContact($role, (array) $src);
        }
        if ($nameservers !== []) {
            $params['Nameservers'] = implode(',', $nameservers);
        }
        if (!empty($extra['add_free_whoisguard'])) {
            $params['AddFreeWhoisguard'] = 'yes';
            $params['WGEnabled'] = 'yes';
        }

        $xml = $this->call('namecheap.domains.create', $params);
        if ($xml === null) {
            return ['success' => false, 'error' => 'Namecheap API call failed'];
        }
        $err = $this->extractError($xml);
        if ($err !== null) {
            return ['success' => false, 'error' => $err];
        }
        $result = $xml->CommandResponse->DomainCreateResult ?? null;
        $registered = $result !== null && self::asBool((string) ($result['Registered'] ?? 'false'));
        if (!$registered) {
            return ['success' => false, 'error' => 'Domain not registered'];
        }
        $expiresIso = self::namecheapDateToIso((string) ($result['DomainExpiry'] ?? ''));
        return [
            'success' => true,
            'registrar_id' => (string) ($result['DomainID'] ?? ''),
            'expires_at' => $expiresIso ?: date('Y-m-d H:i:s', strtotime("+{$years} years") ?: time()),
        ];
    }

    public function renew(string $domain, int $years): array
    {
        $xml = $this->call('namecheap.domains.renew', [
            'DomainName' => $domain,
            'Years' => (string) max(1, $years),
        ]);
        if ($xml === null) {
            return ['success' => false, 'error' => 'Namecheap API call failed'];
        }
        $err = $this->extractError($xml);
        if ($err !== null) {
            return ['success' => false, 'error' => $err];
        }
        $result = $xml->CommandResponse->DomainRenewResult ?? null;
        $renew = $result !== null && self::asBool((string) ($result['Renew'] ?? 'false'));
        if (!$renew) {
            return ['success' => false, 'error' => 'Renewal not confirmed'];
        }
        $expires = self::namecheapDateToIso((string) ($result['DomainDetails']->ExpiredDate ?? ''));
        return [
            'success' => true,
            'expires_at' => $expires ?: date('Y-m-d H:i:s', strtotime("+{$years} years") ?: time()),
        ];
    }

    public function transfer(string $domain, string $authCode, array $contacts): array
    {
        $params = [
            'DomainName' => $domain,
            'Years' => '1',
            'EPPCode' => $authCode,
            'AddFreeWhoisguard' => 'no',
            'WGEnabled' => 'no',
        ];
        $xml = $this->call('namecheap.domains.transfer.create', $params);
        if ($xml === null) {
            return ['success' => false, 'error' => 'Namecheap API call failed'];
        }
        $err = $this->extractError($xml);
        if ($err !== null) {
            return ['success' => false, 'error' => $err];
        }
        $result = $xml->CommandResponse->DomainTransferCreateResult ?? null;
        $statusOk = $result !== null && self::asBool((string) ($result['IsSuccess'] ?? 'false'));
        if (!$statusOk) {
            return ['success' => false, 'error' => 'Transfer not initiated'];
        }
        return [
            'success' => true,
            'transfer_id' => (string) ($result['TransferID'] ?? ''),
        ];
    }

    public function getNameservers(string $domain): array
    {
        [$sld, $tld] = self::splitDomain($domain);
        $xml = $this->call('namecheap.domains.dns.getList', ['SLD' => $sld, 'TLD' => $tld]);
        if ($xml === null) {
            return [];
        }
        $result = $xml->CommandResponse->DomainDNSGetListResult ?? null;
        if ($result === null) {
            return [];
        }
        $hosts = [];
        foreach ($result->Nameserver as $ns) {
            $hosts[] = (string) $ns;
        }
        return $hosts;
    }

    public function setNameservers(string $domain, array $hosts): bool
    {
        [$sld, $tld] = self::splitDomain($domain);
        $params = ['SLD' => $sld, 'TLD' => $tld];
        if ($hosts === []) {
            $xml = $this->call('namecheap.domains.dns.setDefault', $params);
        } else {
            $params['Nameservers'] = implode(',', $hosts);
            $xml = $this->call('namecheap.domains.dns.setCustom', $params);
        }
        if ($xml === null || $this->extractError($xml) !== null) {
            return false;
        }
        $result = $xml->CommandResponse->DomainDNSSetCustomResult
            ?? $xml->CommandResponse->DomainDNSSetDefaultResult
            ?? null;
        return $result !== null && self::asBool((string) ($result['Updated'] ?? 'false'));
    }

    public function getEppCode(string $domain): ?string
    {
        // Namecheap does not expose EPP retrieval via their public API — owners must
        // request the auth code via their dashboard. Return null with no error.
        return null;
    }

    public function setLock(string $domain, bool $locked): bool
    {
        $xml = $this->call('namecheap.domains.setRegistrarLock', [
            'DomainName' => $domain,
            'LockAction' => $locked ? 'LOCK' : 'UNLOCK',
        ]);
        if ($xml === null || $this->extractError($xml) !== null) {
            return false;
        }
        $result = $xml->CommandResponse->DomainSetRegistrarLockResult ?? null;
        return $result !== null && self::asBool((string) ($result['IsSuccess'] ?? 'false'));
    }

    public function setPrivacy(string $domain, bool $enabled): bool
    {
        $listXml = $this->call('namecheap.whoisguard.getList', ['ListType' => 'ALLOCATED']);
        if ($listXml === null) {
            return false;
        }
        $whoisguard = null;
        foreach ($listXml->CommandResponse->WhoisguardGetListResult->Whoisguard ?? [] as $wg) {
            if (strcasecmp((string) ($wg['ForDomain'] ?? ''), $domain) === 0) {
                $whoisguard = $wg;
                break;
            }
        }
        if ($whoisguard === null) {
            return false;
        }
        $wgId = (string) ($whoisguard['ID'] ?? '');
        $cmd = $enabled ? 'namecheap.whoisguard.enable' : 'namecheap.whoisguard.disable';
        $params = ['WhoisguardID' => $wgId];
        if ($enabled) {
            $params['ForwardedToEmail'] = (string) ($this->cfg['privacy_forward_email'] ?? '');
        }
        $resp = $this->call($cmd, $params);
        return $resp !== null && $this->extractError($resp) === null;
    }

    public function setAutoRenew(string $domain, bool $enabled): bool
    {
        // Namecheap does not expose auto-renew toggling via API.
        return false;
    }

    public function listDnsRecords(string $domain): array
    {
        [$sld, $tld] = self::splitDomain($domain);
        $xml = $this->call('namecheap.domains.dns.getHosts', ['SLD' => $sld, 'TLD' => $tld]);
        if ($xml === null) {
            return [];
        }
        $result = $xml->CommandResponse->DomainDNSGetHostsResult ?? null;
        if ($result === null) {
            return [];
        }
        $records = [];
        foreach ($result->host as $host) {
            $records[] = [
                'type' => (string) ($host['Type'] ?? ''),
                'name' => (string) ($host['Name'] ?? ''),
                'content' => (string) ($host['Address'] ?? ''),
                'ttl' => (int) ($host['TTL'] ?? 1800),
                'priority' => isset($host['MXPref']) ? (int) $host['MXPref'] : null,
            ];
        }
        return $records;
    }

    public function setDnsRecords(string $domain, array $records): bool
    {
        [$sld, $tld] = self::splitDomain($domain);
        $params = ['SLD' => $sld, 'TLD' => $tld];
        $i = 1;
        foreach ($records as $r) {
            $params["HostName{$i}"] = (string) ($r['name'] ?? '@');
            $params["RecordType{$i}"] = strtoupper((string) ($r['type'] ?? 'A'));
            $params["Address{$i}"] = (string) ($r['content'] ?? '');
            $params["TTL{$i}"] = (string) (int) ($r['ttl'] ?? 1800);
            if (strtoupper((string) ($r['type'] ?? '')) === 'MX' && isset($r['priority'])) {
                $params["MXPref{$i}"] = (string) (int) $r['priority'];
                $params['EmailType'] = 'MX';
            }
            $i++;
        }
        $xml = $this->call('namecheap.domains.dns.setHosts', $params);
        if ($xml === null || $this->extractError($xml) !== null) {
            return false;
        }
        $result = $xml->CommandResponse->DomainDNSSetHostsResult ?? null;
        return $result !== null && self::asBool((string) ($result['IsSuccess'] ?? 'false'));
    }

    /**
     * Issue a Namecheap XML API command. Returns the parsed response or null
     * on transport / parse failure. Callers must additionally inspect for
     * Errors via extractError().
     *
     * @param array<string,string> $params
     */
    public function call(string $command, array $params): ?\SimpleXMLElement
    {
        if (!$this->isConfigured()) {
            return null;
        }
        $query = array_merge([
            'ApiUser' => (string) $this->cfg['api_user'],
            'ApiKey' => (string) $this->cfg['api_key'],
            'UserName' => (string) ($this->cfg['username'] ?? $this->cfg['api_user']),
            'ClientIp' => (string) ($this->cfg['client_ip'] ?? '127.0.0.1'),
            'Command' => $command,
        ], $params);

        $resp = $this->http->get($this->baseUrl(), ['Accept' => 'application/xml'], $query);
        if (!$resp->ok()) {
            return null;
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($resp->body);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        return $xml === false ? null : $xml;
    }

    public function extractError(\SimpleXMLElement $xml): ?string
    {
        $status = (string) ($xml['Status'] ?? '');
        if ($status !== '' && strcasecmp($status, 'OK') !== 0) {
            $errors = $xml->Errors->Error ?? null;
            if ($errors !== null) {
                $msgs = [];
                foreach ($errors as $error) {
                    $msgs[] = trim((string) $error);
                }
                if ($msgs !== []) {
                    return implode('; ', array_filter($msgs));
                }
            }
            return 'Namecheap returned status: ' . $status;
        }
        return null;
    }

    public function isConfigured(): bool
    {
        return !empty($this->cfg['api_user']) && !empty($this->cfg['api_key']);
    }

    /**
     * @return array{0:string,1:string}
     */
    public static function splitDomain(string $domain): array
    {
        $parts = explode('.', $domain, 2);
        if (count($parts) === 2) {
            return [$parts[0], $parts[1]];
        }
        return [$domain, ''];
    }

    /**
     * @param array<string,mixed> $contact
     * @return array<string,string>
     */
    private function mapContact(string $role, array $contact): array
    {
        $map = [
            'FirstName' => $contact['first_name'] ?? $contact['firstName'] ?? 'John',
            'LastName' => $contact['last_name'] ?? $contact['lastName'] ?? 'Doe',
            'Address1' => $contact['address1'] ?? $contact['address'] ?? '1 Main St',
            'City' => $contact['city'] ?? 'Dhaka',
            'StateProvince' => $contact['state'] ?? $contact['province'] ?? 'Dhaka',
            'PostalCode' => $contact['postal_code'] ?? $contact['zip'] ?? '1000',
            'Country' => $contact['country'] ?? 'BD',
            'Phone' => $contact['phone'] ?? '+880.1700000000',
            'EmailAddress' => $contact['email'] ?? 'admin@example.com',
        ];
        if (!empty($contact['organization'])) {
            $map['OrganizationName'] = (string) $contact['organization'];
        }
        $out = [];
        foreach ($map as $k => $v) {
            $out[$role . $k] = (string) $v;
        }
        return $out;
    }

    private static function asBool(string $value): bool
    {
        return in_array(strtolower($value), ['true', 'yes', '1', 'enabled'], true);
    }

    private static function namecheapDateToIso(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        // Namecheap returns dates like "MM/DD/YYYY".
        $ts = strtotime(str_replace('/', '-', $value)) ?: strtotime($value);
        if ($ts === false) {
            return null;
        }
        return date('Y-m-d H:i:s', $ts);
    }
}
