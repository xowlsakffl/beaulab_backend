<?php

namespace App\Domains\BeautyExpert\Actions\Staff;

use App\Domains\BeautyExpert\Dto\Staff\BeautyExpertForStaffDto;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\BeautyExpert\Queries\Staff\BeautyExpertListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class BeautyExpertListForStaffAction
{
    public function __construct(private readonly BeautyExpertListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', BeautyExpert::class);

        $paginator = $this->query->paginate($filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($expert) => BeautyExpertForStaffDto::fromModel($expert)->toArray())
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
