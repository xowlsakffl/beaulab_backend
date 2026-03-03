<?php

namespace App\Domains\HospitalVideoRequest\Actions\Staff;

use App\Domains\HospitalVideoRequest\Dto\Staff\HospitalVideoRequestForStaffDto;
use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;
use App\Domains\HospitalVideoRequest\Queries\Staff\HospitalVideoRequestListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoRequestListForStaffAction
{
    public function __construct(private readonly HospitalVideoRequestListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalVideoRequest::class);

        $paginator = $this->query->paginate($filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($videoRequest) => HospitalVideoRequestForStaffDto::fromModel($videoRequest)->toArray())
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
