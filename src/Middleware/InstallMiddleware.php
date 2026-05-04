<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use Closure;

final class InstallMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!App::getInstance()->isInstalled() && !str_starts_with($request->path(), '/install')) {
            return Response::redirect('/install');
        }
        return $next($request);
    }
}
