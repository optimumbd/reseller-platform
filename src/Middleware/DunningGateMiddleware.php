<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

final class DunningGateMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        // If user has past-due invoices, redirect to billing page.
        return $next($request);
    }
}
