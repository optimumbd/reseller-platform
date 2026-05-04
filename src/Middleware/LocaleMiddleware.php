<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use Closure;

final class LocaleMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = App::getInstance()->session;
        $locale = (string) ($session->get('locale') ?? config('app.locale', 'en'));
        $session->put('locale', $locale);
        return $next($request);
    }
}
