<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use Closure;

final class ThrottleMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $limiter = new RateLimiter(App::getInstance()->cache);
        $key = 'route:' . $request->ip . ':' . $request->path();
        if (!$limiter->attempt($key, 30, 60)) {
            $retry = $limiter->resetIn($key);
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Too Many Requests', 'retry_after' => $retry], 429);
            }
            return Response::view('errors/429', ['retry_after' => $retry], 429);
        }
        return $next($request);
    }
}
