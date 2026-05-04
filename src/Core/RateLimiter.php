<?php

declare(strict_types=1);

namespace App\Core;

/**
 * File-cache backed rate limiter (token-bucket-ish).
 */
final class RateLimiter
{
    public function __construct(private readonly Cache $cache) {}

    /**
     * Returns true if request is allowed. Increments counter on allow.
     */
    public function attempt(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $bucket = $this->cache->get('rl:' . $key, ['count' => 0, 'reset' => time() + $windowSeconds]);
        if ($bucket['reset'] < time()) {
            $bucket = ['count' => 0, 'reset' => time() + $windowSeconds];
        }
        if ($bucket['count'] >= $maxAttempts) {
            return false;
        }
        $bucket['count']++;
        $this->cache->put('rl:' . $key, $bucket, $windowSeconds);
        return true;
    }

    public function remaining(string $key, int $maxAttempts): int
    {
        $bucket = $this->cache->get('rl:' . $key, ['count' => 0, 'reset' => 0]);
        return max(0, $maxAttempts - (int) ($bucket['count'] ?? 0));
    }

    public function resetIn(string $key): int
    {
        $bucket = $this->cache->get('rl:' . $key, ['reset' => time()]);
        return max(0, (int) ($bucket['reset'] ?? time()) - time());
    }

    public function clear(string $key): void
    {
        $this->cache->forget('rl:' . $key);
    }
}
