<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;
use Closure;

final class ActivityLoggerMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        try {
            $log = new ActivityLog([
                'user_id' => App::getInstance()->session->userId(),
                'actor_type' => 'user',
                'action' => $request->method() . ' ' . $request->path(),
                'description' => null,
                'ip' => $request->ip,
                'user_agent' => substr($request->userAgent, 0, 250),
            ]);
            $log->save();
        } catch (\Throwable) {
            // swallow — activity log is best-effort
        }
        return $response;
    }
}
