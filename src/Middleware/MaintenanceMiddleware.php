<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

final class MaintenanceMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.maintenance') && !is_admin() && $request->path() !== '/admin/login') {
            return Response::view('errors/503', [], 503);
        }
        return $next($request);
    }
}
