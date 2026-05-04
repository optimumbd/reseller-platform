<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple synchronous event dispatcher.
 *
 * Register listeners in a service provider:
 *   $events->listen(OrderCompleted::class, [SendInvoice::class, 'handle']);
 */
final class EventDispatcher
{
    /** @var array<string,callable[]> */
    private array $listeners = [];

    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function dispatch(object $event): void
    {
        $name = $event::class;
        foreach ($this->listeners[$name] ?? [] as $listener) {
            $listener($event);
        }
    }
}
