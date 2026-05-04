<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;

final class MigrateCommand extends Command
{
    public function name(): string { return 'migrate'; }
    public function description(): string { return 'Run pending database migrations.'; }

    public function handle(array $args): int
    {
        $migrationsPath = $this->app->basePath('database/migrations');
        if (!is_dir($migrationsPath)) {
            $this->err('No migrations directory found at: ' . $migrationsPath);
            return 1;
        }

        // Ensure migrations table exists
        $this->app->db->pdo()->exec('CREATE TABLE IF NOT EXISTS migrations (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL UNIQUE,
            executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $applied = $this->app->db->select('SELECT name FROM migrations');
        $appliedNames = array_map(fn ($r) => (string) ($r['name'] ?? ''), $applied);

        $files = glob($migrationsPath . '/*.sql') ?: [];
        sort($files);

        $count = 0;
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $appliedNames, true)) {
                continue;
            }
            $this->out("Migrating: {$name}");
            $sql = (string) file_get_contents($file);
            // Split on semicolons that end a statement (best-effort)
            $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql) ?: []));
            foreach ($statements as $stmt) {
                if ($stmt === '' || str_starts_with($stmt, '--')) {
                    continue;
                }
                $this->app->db->pdo()->exec($stmt);
            }
            $this->app->db->insert('migrations', ['name' => $name]);
            $count++;
        }

        $this->out($count === 0 ? 'No new migrations.' : "Applied {$count} migrations.");
        return 0;
    }
}
