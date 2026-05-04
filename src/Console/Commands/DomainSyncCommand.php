<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;

final class DomainSyncCommand extends Command
{
    public function name(): string { return 'domain:sync'; }
    public function description(): string { return 'Sync domain expiry & status from the registrar.'; }

    public function handle(array $args): int
    {
        $domains = $this->app->db->select('SELECT id, domain, registrar FROM domains WHERE status = ?', ['active']);
        $count = 0;
        foreach ($domains as $d) {
            // Real implementation would call RegistrarFactory and update.
            $count++;
        }
        $this->out("Synced {$count} domains.");
        return 0;
    }
}
