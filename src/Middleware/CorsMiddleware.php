<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

final class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $request->method() === 'OPTIONS' ? new Response('', 204) : $next($request);
        $response->headers['Access-Control-Allow-Origin'] = $request->header('origin', '*') ?? '*';
        $response->headers['Access-Control-Allow-Methods'] = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
        $response->headers['Access-Control-Allow-Headers'] = 'Authorization, Content-Type, X-Requested-With, X-CSRF-Token';
        $response->headers['Access-Control-Max-Age'] = '600';
        return $response;
    }
}
