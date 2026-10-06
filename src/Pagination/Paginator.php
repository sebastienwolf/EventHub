<?php

namespace App\Pagination;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

final class Paginator
{
    /**
     * @return PaginatedResult<mixed>
     */
    public function paginate(QueryBuilder $queryBuilder, int $page, int $limit): PaginatedResult
    {
        $query = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery();

        // Doctrine's paginator handles fetch-joined collections correctly when counting
        $paginator = new DoctrinePaginator($query);

        return new PaginatedResult(
            items: iterator_to_array($paginator, false),
            total: \count($paginator),
            page: $page,
            limit: $limit,
        );
    }
}
