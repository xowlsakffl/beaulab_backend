<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalVideo\Queries\Staff\HospitalVideoHospitalOptionListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoHospitalOptionListForStaffAction
{
    public function __construct(
        private readonly HospitalVideoHospitalOptionListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Hospital::class);

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
