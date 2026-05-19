<?php

namespace App\Common\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PaginatedResponse
{
    /**
     * @param  array<string, mixed>  $extraMeta
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public static function fromPaginator(LengthAwarePaginator $paginator, callable $mapper, array $extraMeta = []): array
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
            ] + $extraMeta,
        ];
    }
}
