<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use App\Models\ApiToken;
use App\Models\User;
use Closure;

final class ApiAuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (!$token) {
            return Response::json(['error' => 'Bearer token required'], 401);
        }
        $apiToken = ApiToken::findByPlainToken($token);
        if (!$apiToken) {
            return Response::json(['error' => 'Invalid token'], 401);
        }
        if ($apiToken->expires_at && strtotime((string) $apiToken->expires_at) < time()) {
            return Response::json(['error' => 'Token expired'], 401);
        }
        $apiToken->last_used_at = now();
        $apiToken->save();
        App::getInstance()->session->put('user_id', (int) $apiToken->user_id);
        return $next($request);
    }
}
