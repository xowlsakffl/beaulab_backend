<?php

namespace App\Domains\HospitalDoctor\Actions\Staff;

use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalDoctor\Queries\Staff\DoctorHospitalOptionListForStaffQuery;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Support\Facades\Gate;

final class DoctorHospitalOptionListForStaffAction
{
    public function __construct(
        private readonly DoctorHospitalOptionListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('create', HospitalDoctor::class);

        $items = $this->query->get($filters)
            ->map(static fn (Hospital $hospital): array => [
                'id' => (int) $hospital->id,
                'name' => (string) $hospital->name,
                'business_number' => $hospital->businessRegistration?->business_number,
            ])
            ->values()
            ->all();

        return [
            'items' => $items,
            'meta' => null,
        ];
    }
}
