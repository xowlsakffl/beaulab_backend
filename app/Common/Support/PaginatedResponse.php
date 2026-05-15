<?php

namespace App\Common\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PaginatedResponse
{
    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public static function fromPaginator(LengthAwarePaginator $paginator, callable $mapper): array
    {
        return [
            'items' => collect($paginator->items())
                ->map($mapper)
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

    public static function paginateWithFallback(
        callable $queryFactory,
        int $perPage,
        string $pageName,
        int $page,
    ): LengthAwarePaginator {
        $page = max(1, $page);
        $paginator = $queryFactory()->paginate(
            perPage: $perPage,
            pageName: $pageName,
            page: $page,
        );

        if ($page === 1 || $paginator->total() === 0 || $paginator->items() !== []) {
            return $paginator;
        }

        return $queryFactory()->paginate(
            perPage: $perPage,
            pageName: $pageName,
            page: 1,
        );
    }
}
