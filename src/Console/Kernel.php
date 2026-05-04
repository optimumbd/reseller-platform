<?php

declare(strict_types=1);

namespace App\Console;

use App\Console\Commands\CacheClearCommand;
use App\Console\Commands\DomainSyncCommand;
use App\Console\Commands\InvoiceRemindCommand;
use App\Console\Commands\KeyGenerateCommand;
use App\Console\Commands\MigrateCommand;
use App\Console\Commands\QueueWorkCommand;
use App\Console\Commands\RouteListCommand;
use App\Console\Commands\ScheduleRunCommand;
use App\Console\Commands\SeedCommand;
use App\Core\App;

final class Kernel
{
    /** @var array<string,Command> */
    private array $commands = [];

    public function __construct(private readonly App $app)
    {
        $this->register(new MigrateCommand($app));
        $this->register(new SeedCommand($app));
        $this->register(new QueueWorkCommand($app));
        $this->register(new ScheduleRunCommand($app));
        $this->register(new DomainSyncCommand($app));
        $this->register(new InvoiceRemindCommand($app));
        $this->register(new CacheClearCommand($app));
        $this->register(new KeyGenerateCommand($app));
        $this->register(new RouteListCommand($app));
    }

    public function register(Command $cmd): void
    {
        $this->commands[$cmd->name()] = $cmd;
    }

    /** @param list<string> $argv */
    public function handle(array $argv): int
    {
        $name = $argv[1] ?? null;
        if ($name === null || $name === 'list' || $name === 'help' || $name === '--help' || $name === '-h') {
            $this->printList();
            return 0;
        }
        if (!isset($this->commands[$name])) {
            fwrite(STDERR, "Unknown command: {$name}" . PHP_EOL);
            $this->printList();
            return 1;
        }
        $args = array_slice($argv, 2);
        try {
            return $this->commands[$name]->handle($args);
        } catch (\Throwable $e) {
            fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
            if ($this->app->isDebug()) {
                fwrite(STDERR, $e->getTraceAsString() . PHP_EOL);
            }
            return 1;
        }
    }

    private function printList(): void
    {
        $this->out("Reseller Platform CLI");
        $this->out("Usage: php bin/console <command> [...args]");
        $this->out("");
        $this->out("Commands:");
        foreach ($this->commands as $cmd) {
            $this->out(sprintf("  %-22s %s", $cmd->name(), $cmd->description()));
        }
    }

    private function out(string $line): void
    {
        fwrite(STDOUT, $line . PHP_EOL);
    }
}
