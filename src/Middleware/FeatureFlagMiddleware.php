<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

final class FeatureFlagMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
