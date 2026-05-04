<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\TldPricing;
use App\Services\Registrar\RegistrarFactory;

final class DomainController extends BaseController
{
    public function check(Request $request): Response
    {
        $domain = strtolower(trim((string) $request->input('domain', '')));
        if ($domain === '') {
            return $this->json(['error' => 'domain required'], 422);
        }
        $registrar = RegistrarFactory::default();
        $r = $registrar->checkAvailability($domain);
        $tld = substr($domain, strpos($domain, '.') + 1);
        $pricing = TldPricing::whereOne('tld = :t', ['t' => $tld]);
        return $this->json([
            'domain' => $domain,
            'available' => (bool) ($r['available'] ?? false),
            'price' => $pricing ? (float) $pricing->register_price : null,
            'currency' => $pricing ? (string) $pricing->currency : 'USD',
        ]);
    }
}
