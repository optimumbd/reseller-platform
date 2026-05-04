<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\App;

abstract class Command
{
    public function __construct(protected App $app) {}

    /** @return string CLI name, e.g. "migrate", "queue:work" */
    abstract public function name(): string;

    /** @return string Human-readable description */
    public function description(): string
    {
        return '';
    }

    /**
     * @param list<string> $args
     * @return int Exit code (0 = ok)
     */
    abstract public function handle(array $args): int;

    protected function out(string $line): void
    {
        fwrite(STDOUT, $line . PHP_EOL);
    }

    protected function err(string $line): void
    {
        fwrite(STDERR, $line . PHP_EOL);
    }
}
