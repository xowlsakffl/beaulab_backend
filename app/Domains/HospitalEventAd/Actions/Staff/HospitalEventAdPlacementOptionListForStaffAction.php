<?php

namespace App\Domains\HospitalEventAd\Actions\Staff;

use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalEventAd\Queries\HospitalEventAdPricingQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalEventAdPlacementOptionListForStaffAction
{
    public function __construct(private readonly HospitalEventAdPricingQuery $pricingQuery) {}

    public function execute(): array
    {
        Gate::authorize('create', HospitalEventAd::class);
        $costs = $this->pricingQuery->costs();

        return [
            'items' => collect(HospitalEventAd::placements())
                ->map(static fn (string $placement): array => [
                    'value' => $placement,
                    'label' => HospitalEventAd::placementLabel($placement),
                    'group_label' => HospitalEventAd::placementGroupLabel($placement),
                    'category_required' => HospitalEventAd::requiresCategory($placement),
                    'category_usage' => HospitalEventAd::categoryUsageForPlacement($placement),
                    'slot_limit' => HospitalEventAd::WEEKLY_SLOT_LIMIT,
                    'cost' => $costs[$placement],
                    'start_day_of_week' => HospitalEventAd::startDayOfWeek($placement),
                    'start_day_label' => HospitalEventAd::startDayLabel($placement),
                ])
                ->values()
                ->all(),
            'meta' => null,
        ];
    }
}
