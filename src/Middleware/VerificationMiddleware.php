<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

final class VerificationMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth_user();
        if ($user && empty($user['email_verified_at'])) {
            return Response::view('auth/verify-email-notice', ['user' => $user]);
        }
        return $next($request);
    }
}
