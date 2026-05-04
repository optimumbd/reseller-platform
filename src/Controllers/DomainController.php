<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Helpers\IdnHelper;
use App\Models\TldPricing;
use App\Services\Registrar\RegistrarFactory;

final class DomainController extends BaseController
{
    public function search(Request $request): Response
    {
        $query = trim((string) $request->input('q', ''));
        $results = [];
        $tlds = array_map(fn ($t) => (string) $t->tld, TldPricing::where('is_active = 1 ORDER BY sort_order ASC, register_price ASC LIMIT 24'));
        if ($query !== '') {
            $base = strtolower(preg_replace('/[^a-z0-9\-\.]/i', '', $query) ?? '');
            if (str_contains($base, '.')) {
                $tlds = [substr($base, strpos($base, '.') + 1)];
                $base = explode('.', $base)[0];
            }
            $registrar = RegistrarFactory::default();
            foreach ($tlds as $tld) {
                $domain = $base . '.' . $tld;
                $tldRow = TldPricing::whereOne('tld = :t', ['t' => $tld]);
                $price = $tldRow ? (float) $tldRow->register_price : 9.99;
                try {
                    $r = $registrar->checkAvailability($domain);
                } catch (\Throwable) {
                    $r = ['available' => false];
                }
                $results[] = [
                    'domain' => $domain,
                    'available' => (bool) ($r['available'] ?? false),
                    'price' => $price,
                    'currency' => $tldRow ? (string) $tldRow->currency : 'USD',
                ];
            }
        }
        return $this->view('domains/search', [
            'query' => $query,
            'results' => $results,
        ]);
    }

    public function whois(Request $request): Response
    {
        $domain = trim((string) $request->input('domain', ''));
        $output = '';
        if ($domain !== '' && function_exists('socket_create')) {
            // Minimal WHOIS via tcp/43; fall back to a fake response.
            $output = "WHOIS lookup is configured to use external service. Configure WhoisService to enable.";
        }
        return $this->view('domains/whois', ['domain' => $domain, 'output' => $output]);
    }

    public function dnsLookup(Request $request): Response
    {
        $domain = trim((string) $request->input('domain', ''));
        $records = [];
        if ($domain !== '' && function_exists('dns_get_record')) {
            $records = @dns_get_record($domain, DNS_ALL) ?: [];
        }
        return $this->view('domains/dns-lookup', ['domain' => $domain, 'records' => $records]);
    }

    public function generator(Request $request): Response
    {
        $keyword = trim((string) $request->input('keyword', ''));
        $suggestions = [];
        if ($keyword !== '') {
            $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $keyword) ?? '');
            $suffixes = ['hub', 'app', 'lab', 'pro', 'co', 'io', 'tech', 'cloud', 'space', 'world'];
            $tlds = ['com', 'net', 'io', 'co', 'app'];
            foreach ($suffixes as $sfx) {
                foreach ($tlds as $tld) {
                    $suggestions[] = $base . $sfx . '.' . $tld;
                    if (count($suggestions) >= 30) {
                        break 2;
                    }
                }
            }
        }
        return $this->view('domains/generator', ['keyword' => $keyword, 'suggestions' => $suggestions]);
    }

    public function transferIndex(Request $request): Response
    {
        return $this->view('domains/transfer');
    }
}
