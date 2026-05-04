<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use Closure;

final class TwoFactorMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = App::getInstance()->session;
        if ($session->get('2fa_pending')) {
            return Response::redirect('/two-factor/challenge');
        }
        return $next($request);
    }
}
