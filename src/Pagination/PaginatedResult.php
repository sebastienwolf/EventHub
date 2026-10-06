<?php

namespace App\Pagination;

/**
 * @template T
 */
final class PaginatedResult
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $limit,
    ) {
    }

    public function getPages(): int
    {
        return max(1, (int) ceil($this->total / $this->limit));
    }

    /**
     * @return array{page: int, limit: int, total: int, pages: int}
     */
    public function getMeta(): array
    {
        return [
            'page' => $this->page,
            'limit' => $this->limit,
            'total' => $this->total,
            'pages' => $this->getPages(),
        ];
    }
}
