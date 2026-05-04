<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

final class AdminMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!is_admin()) {
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Forbidden'], 403);
            }
            return Response::view('errors/403', [], 403);
        }
        return $next($request);
    }
}
