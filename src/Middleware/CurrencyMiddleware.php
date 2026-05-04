<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use Closure;

final class CurrencyMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = App::getInstance()->session;
        $currency = (string) ($session->get('currency') ?? config('currency.default', 'USD'));
        $session->put('currency', $currency);
        return $next($request);
    }
}
