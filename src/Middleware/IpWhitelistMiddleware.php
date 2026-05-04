<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

final class IpWhitelistMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = (string) config_setting('admin_ip_whitelist', '');
        if ($allowed === '') {
            return $next($request);
        }
        $list = array_map('trim', explode(',', $allowed));
        if (!in_array($request->ip, $list, true)) {
            return Response::view('errors/403', [], 403);
        }
        return $next($request);
    }
}
