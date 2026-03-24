<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalVideo\Queries\Staff\HospitalVideoDoctorOptionListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoDoctorOptionListForStaffAction
{
    public function __construct(
        private readonly HospitalVideoDoctorOptionListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalDoctor::class);

        $items = $this->query->get($filters)
            ->map(static fn (HospitalDoctor $doctor): array => [
                'id' => (int) $doctor->id,
                'name' => (string) $doctor->name,
                'position' => $doctor->position,
            ])
            ->values()
            ->all();

        return [
            'items' => $items,
            'meta' => null,
        ];
    }
}
