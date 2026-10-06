<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Mapped from the query string with #[MapQueryString]: ?page=2&limit=10.
 */
final class PaginationQuery
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $page = 1,

        #[Assert\Range(min: 1, max: 100)]
        public readonly int $limit = 20,
    ) {
    }
}
