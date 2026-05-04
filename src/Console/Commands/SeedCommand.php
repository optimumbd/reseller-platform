<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;

final class SeedCommand extends Command
{
    public function name(): string { return 'seed'; }
    public function description(): string { return 'Seed the database with sample data.'; }

    public function handle(array $args): int
    {
        $seeder = $this->app->basePath('database/seeders/seed.php');
        if (!file_exists($seeder)) {
            $this->err('No seeder found at: ' . $seeder);
            return 1;
        }
        require $seeder;
        $this->out('Database seeded.');
        return 0;
    }
}
