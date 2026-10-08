<?php

namespace App\Domains\HospitalEventAd\Queries;

use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalEventAd\Models\HospitalEventAdPricingVersion;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class HospitalEventAdPricingQuery
{
    public function versions(string $group): Builder
    {
        return HospitalEventAdPricingVersion::query()
            ->whereIn('group_id', DB::table('hospital_event_ad_pricing_groups')->select('id')->where('group_key', $group))
            ->whereNull('cancelled_at');
    }

    public function current(string $group, CarbonImmutable $at): ?HospitalEventAdPricingVersion
    {
        return $this->versions($group)->where('effective_at', '<=', $at)
            ->orderByDesc('effective_at')->orderByDesc('id')->first();
    }

    public function pending(string $group, CarbonImmutable $at): ?HospitalEventAdPricingVersion
    {
        return $this->versions($group)->where('effective_at', '>', $at)
            ->orderBy('effective_at')->orderBy('id')->first();
    }

    public function cost(string $placement): int
    {
        foreach (HospitalEventAd::placementGroups() as $group => $placements) {
            if (in_array($placement, $placements, true)) {
                return (int) ($this->current($group, CarbonImmutable::now())?->prices[$placement] ?? HospitalEventAd::placementCost($placement));
            }
        }

        throw new \InvalidArgumentException('지원하지 않는 광고 위치입니다.');
    }

    public function costs(): array
    {
        $now = CarbonImmutable::now();
        $costs = [];
        foreach (HospitalEventAd::placementGroups() as $group => $placements) {
            $version = $this->current($group, $now);
            foreach ($placements as $placement) {
                $costs[$placement] = (int) ($version?->prices[$placement] ?? HospitalEventAd::placementCost($placement));
            }
        }

        return $costs;
    }
}
