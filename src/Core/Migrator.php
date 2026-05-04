<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Runs versioned SQL migrations from database/migrations/.
 *
 * Each file: YYYYMMDD_HHMMSS_description.sql — split top-down by `-- DOWN`
 * marker if you want a rollback section.
 */
final class Migrator
{
    public function __construct(private readonly Database $db, private readonly string $migrationsDir) {}

    /**
     * @return string[] Names of migrations that were applied.
     */
    public function migrate(): array
    {
        $this->ensureTable();
        $applied = $this->appliedNames();
        $files = glob($this->migrationsDir . '/*.sql') ?: [];
        sort($files);
        $batch = (int) ($this->db->scalar('SELECT COALESCE(MAX(batch), 0) FROM migrations') ?? 0) + 1;
        $ran = [];
        foreach ($files as $file) {
            $name = basename($file, '.sql');
            if (in_array($name, $applied, true)) {
                continue;
            }
            $sql = (string) file_get_contents($file);
            $up = explode('-- DOWN', $sql)[0];
            $this->db->transaction(function () use ($up, $name, $batch) {
                foreach ($this->splitStatements($up) as $stmt) {
                    if (trim($stmt) !== '') {
                        $this->db->pdo()->exec($stmt);
                    }
                }
                $this->db->insert('migrations', ['name' => $name, 'batch' => $batch]);
            });
            $ran[] = $name;
        }
        return $ran;
    }

    public function status(): array
    {
        $this->ensureTable();
        $applied = $this->appliedNames();
        $files = glob($this->migrationsDir . '/*.sql') ?: [];
        $rows = [];
        foreach ($files as $file) {
            $name = basename($file, '.sql');
            $rows[] = ['name' => $name, 'applied' => in_array($name, $applied, true)];
        }
        return $rows;
    }

    private function ensureTable(): void
    {
        $this->db->pdo()->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(191) NOT NULL UNIQUE,
                batch INT UNSIGNED NOT NULL,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);
    }

    /** @return string[] */
    private function appliedNames(): array
    {
        $rows = $this->db->select('SELECT name FROM migrations ORDER BY id ASC');
        return array_map(fn ($r) => (string) $r['name'], $rows);
    }

    /** @return string[] */
    private function splitStatements(string $sql): array
    {
        // Naive splitter — fine for our migrations. Don't put `;` inside strings.
        return array_map('trim', preg_split('/;\s*$/m', $sql) ?: []);
    }
}
