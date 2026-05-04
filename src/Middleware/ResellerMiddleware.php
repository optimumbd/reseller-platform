<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

final class ResellerMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth_user();
        $role = $user['role'] ?? 'customer';
        if (!in_array($role, ['reseller', 'admin'], true)) {
            return Response::view('errors/403', [], 403);
        }
        return $next($request);
    }
}
