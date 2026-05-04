<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use Closure;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (App::getInstance()->session->userId() === null) {
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Unauthenticated'], 401);
            }
            App::getInstance()->session->flash('intended', $request->path());
            return Response::redirect('/login');
        }
        return $next($request);
    }
}
