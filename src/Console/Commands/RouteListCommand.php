<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;

final class RouteListCommand extends Command
{
    public function name(): string { return 'route:list'; }
    public function description(): string { return 'Print the registered routes.'; }

    public function handle(array $args): int
    {
        $reflection = new \ReflectionClass($this->app->router);
        $prop = $reflection->getProperty('routes');
        $prop->setAccessible(true);
        /** @var array $routes */
        $routes = $prop->getValue($this->app->router);
        foreach ($routes as $r) {
            $mw = empty($r['middleware']) ? '' : '  [' . implode(',', $r['middleware']) . ']';
            $this->out(sprintf('%-7s %-40s%s', $r['method'], $r['pattern'], $mw));
        }
        return 0;
    }
}
