<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

final class TenantMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        // Tenancy is opt-in. When config('tenancy.enabled') is true, resolve
        // the tenant from the request host or header.
        return $next($request);
    }
}
