<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

final class KycMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth_user();
        if ($user && ($user['kyc_status'] ?? 'not_submitted') !== 'approved') {
            return Response::redirect('/account/verification');
        }
        return $next($request);
    }
}
