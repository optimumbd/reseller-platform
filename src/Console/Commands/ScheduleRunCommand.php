<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;

final class ScheduleRunCommand extends Command
{
    public function name(): string { return 'schedule:run'; }
    public function description(): string { return 'Run scheduled tasks (invoke every minute via cron).'; }

    public function handle(array $args): int
    {
        $now = time();
        $hour = (int) date('G', $now);
        $min = (int) date('i', $now);

        // Every 15 minutes — sync domain expiries & queue work
        if ($min % 15 === 0) {
            (new DomainSyncCommand($this->app))->handle([]);
        }
        // Daily at 02:00 — invoice reminders
        if ($hour === 2 && $min === 0) {
            (new InvoiceRemindCommand($this->app))->handle([]);
        }
        $this->out('schedule:run done');
        return 0;
    }
}
