<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;

final class KeyGenerateCommand extends Command
{
    public function name(): string { return 'key:generate'; }
    public function description(): string { return 'Generate a new APP_KEY in .env (32-byte base64).'; }

    public function handle(array $args): int
    {
        $key = 'base64:' . base64_encode(random_bytes(32));
        $envPath = $this->app->basePath('.env');
        if (!file_exists($envPath)) {
            $this->err('.env not found.');
            return 1;
        }
        $contents = (string) file_get_contents($envPath);
        if (preg_match('/^APP_KEY=.*$/m', $contents)) {
            $contents = (string) preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, $contents);
        } else {
            $contents .= "\nAPP_KEY={$key}\n";
        }
        file_put_contents($envPath, $contents);
        $this->out('APP_KEY set to: ' . $key);
        return 0;
    }
}
