<?php

namespace App\Domains\HospitalFeature\Actions\Staff;

use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalFeature\Models\HospitalFeature;
use App\Domains\HospitalFeature\Queries\Staff\HospitalFeatureListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalFeatureListForStaffAction
{
    public function __construct(
        private readonly HospitalFeatureListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Hospital::class);

        $items = $this->query->get($filters)
            ->map(fn (HospitalFeature $feature): array => [
                'id' => (int) $feature->id,
                'code' => (string) $feature->code,
                'name' => (string) $feature->name,
                'sort_order' => (int) $feature->sort_order,
                'status' => (string) $feature->status,
            ])
            ->values()
            ->all();

        return [
            'items' => $items,
            'meta' => null,
        ];
    }
}
