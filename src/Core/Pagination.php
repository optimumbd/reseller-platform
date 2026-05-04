<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple paginator value object.
 */
final class Pagination
{
    public function __construct(
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
        /** @var array<int,mixed> */
        public readonly array $items,
        public readonly string $baseUrl = '',
    ) {}

    public function totalPages(): int
    {
        return max(1, (int) ceil($this->total / max(1, $this->perPage)));
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->totalPages();
    }

    public function urlFor(int $page): string
    {
        $sep = str_contains($this->baseUrl, '?') ? '&' : '?';
        return $this->baseUrl . $sep . 'page=' . $page;
    }
}
