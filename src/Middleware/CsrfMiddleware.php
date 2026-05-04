<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use Closure;

final class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }
        $token = $request->input('_token') ?? $request->header('x-csrf-token');
        if (!App::getInstance()->session->verifyCsrf($token)) {
            if ($request->expectsJson()) {
                return Response::json(['error' => 'CSRF token mismatch'], 419);
            }
            return Response::view('errors/419', [], 419);
        }
        return $next($request);
    }
}
