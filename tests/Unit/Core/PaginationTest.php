<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Pagination;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    public function test_total_pages_rounds_up(): void
    {
        $p = new Pagination(total: 25, page: 1, perPage: 10, items: []);
        $this->assertSame(3, $p->totalPages());
    }

    public function test_total_pages_is_at_least_one_for_empty_set(): void
    {
        $p = new Pagination(total: 0, page: 1, perPage: 10, items: []);
        $this->assertSame(1, $p->totalPages());
    }

    public function test_has_previous_and_next(): void
    {
        $p = new Pagination(total: 25, page: 2, perPage: 10, items: []);
        $this->assertTrue($p->hasPrevious());
        $this->assertTrue($p->hasNext());

        $first = new Pagination(total: 25, page: 1, perPage: 10, items: []);
        $this->assertFalse($first->hasPrevious());

        $last = new Pagination(total: 25, page: 3, perPage: 10, items: []);
        $this->assertFalse($last->hasNext());
    }

    public function test_url_for_appends_page_with_correct_separator(): void
    {
        $clean = new Pagination(0, 1, 10, [], '/users');
        $this->assertSame('/users?page=4', $clean->urlFor(4));

        $withQuery = new Pagination(0, 1, 10, [], '/users?sort=name');
        $this->assertSame('/users?sort=name&page=4', $withQuery->urlFor(4));
    }
}
