<?php

namespace App\Domains\Doctor\Actions\Staff;

use App\Domains\Doctor\Dto\Staff\DoctorForStaffDto;
use App\Domains\Doctor\Models\Doctor;
use App\Domains\Doctor\Queries\Staff\DoctorListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class DoctorListForStaffAction
{
    public function __construct(private readonly DoctorListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Doctor::class);

        $paginator = $this->query->paginate($filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($doctor) => DoctorForStaffDto::fromModel($doctor)->toArray())
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
