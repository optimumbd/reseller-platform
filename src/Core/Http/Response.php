<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Lightweight HTTP response value object returned by {@see Client}.
 */
final class Response
{
    public function __construct(
        public readonly int $status,
        /** @var array<string,string> */
        public readonly array $headers,
        public readonly string $body,
    ) {}

    public function ok(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function json(): mixed
    {
        return json_decode($this->body, true);
    }
}
