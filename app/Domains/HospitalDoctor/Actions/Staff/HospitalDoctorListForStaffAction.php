<?php

namespace App\Domains\HospitalDoctor\Actions\Staff;

use App\Domains\HospitalDoctor\Dto\Staff\HospitalDoctorForStaffDto;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalDoctor\Queries\Staff\HospitalDoctorListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalDoctorListForStaffAction
{
    public function __construct(private readonly HospitalDoctorListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalDoctor::class);

        $paginator = $this->query->paginate($filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($doctor) => HospitalDoctorForStaffDto::fromModel($doctor)->toArray())
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
