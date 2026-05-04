<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;

final class QueueWorkCommand extends Command
{
    public function name(): string { return 'queue:work'; }
    public function description(): string { return 'Process queued jobs from the database queue.'; }

    public function handle(array $args): int
    {
        $maxIterations = (int) ($args[0] ?? 100);
        $sleep = (int) ($args[1] ?? 3);

        for ($i = 0; $i < $maxIterations; $i++) {
            $job = $this->app->db->selectOne(
                'SELECT * FROM jobs WHERE reserved_at IS NULL AND available_at <= ? ORDER BY id ASC LIMIT 1',
                [now()]
            );
            if (!$job) {
                sleep($sleep);
                continue;
            }
            $jobId = (int) ($job['id'] ?? 0);
            $this->app->db->execute('UPDATE jobs SET reserved_at = ?, attempts = attempts + 1 WHERE id = ?', [now(), $jobId]);
            try {
                $payload = json_decode((string) ($job['payload'] ?? '{}'), true);
                $this->out('Processing job #' . $jobId . ' [' . ($payload['type'] ?? '?') . ']');
                // Job processing dispatch would go here (ProvisioningJob, etc.)
                $this->app->db->execute('DELETE FROM jobs WHERE id = ?', [$jobId]);
            } catch (\Throwable $e) {
                $this->err('Job failed: ' . $e->getMessage());
                $this->app->db->execute('UPDATE jobs SET reserved_at = NULL, last_error = ? WHERE id = ?', [$e->getMessage(), $jobId]);
            }
        }
        return 0;
    }
}
