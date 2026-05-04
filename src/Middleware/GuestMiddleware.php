<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use Closure;

final class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (App::getInstance()->session->userId() !== null) {
            return Response::redirect(is_admin() ? '/admin' : '/account');
        }
        return $next($request);
    }
}
