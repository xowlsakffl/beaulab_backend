<?php

namespace App\Domains\Expert\Actions\Staff;

use App\Domains\Expert\Dto\Staff\ExpertForStaffDto;
use App\Domains\Expert\Models\Expert;
use App\Domains\Expert\Queries\Staff\ExpertListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class ExpertListForStaffAction
{
    public function __construct(private readonly ExpertListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Expert::class);

        $paginator = $this->query->paginate($filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($expert) => ExpertForStaffDto::fromModel($expert)->toArray())
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
}
