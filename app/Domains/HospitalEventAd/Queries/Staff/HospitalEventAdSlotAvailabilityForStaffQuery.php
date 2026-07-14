<?php

namespace App\Domains\HospitalEventAd\Queries\Staff;

use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Illuminate\Support\Carbon;

final class HospitalEventAdSlotAvailabilityForStaffQuery
{
    public function reservedCount(string $placement, ?int $categoryId, Carbon $startAt, ?int $excludeId = null): int
    {
        return HospitalEventAd::query()
            ->where('placement', $placement)
            ->where('start_at', $startAt)
            ->whereIn('allow_status', [
                HospitalEventAd::ALLOW_PENDING,
                HospitalEventAd::ALLOW_REVIEWING,
                HospitalEventAd::ALLOW_APPROVED,
            ])
            ->when($categoryId !== null, fn ($query) => $query
                ->whereHas('categories', fn ($categoryQuery) => $categoryQuery
                    ->where('categories.id', $categoryId)))
            ->when($excludeId !== null, fn ($query) => $query->whereKeyNot($excludeId))
            ->count();
    }
}
