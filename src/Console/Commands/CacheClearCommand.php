<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;

final class CacheClearCommand extends Command
{
    public function name(): string { return 'cache:clear'; }
    public function description(): string { return 'Clear the file-driver cache directory.'; }

    public function handle(array $args): int
    {
        $this->app->cache->flush();
        $this->out('Cache cleared.');
        return 0;
    }
}
