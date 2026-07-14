<?php

namespace App\Domains\HospitalEventAd\Queries\Staff;

use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class HospitalEventAdAvailabilityForStaffQuery
{
    public function __construct(
        private readonly HospitalEventAdSlotAvailabilityForStaffQuery $slotQuery,
    ) {}

    public function get(string $placement, ?int $categoryId, Carbon $month): array
    {
        $weeks = [];
        $cursor = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        while ($cursor->lte($endOfMonth)) {
            if ($cursor->dayOfWeek === CarbonInterface::TUESDAY) {
                $weeks[] = $this->weekAvailability($placement, $categoryId, $cursor);
            }

            $cursor->addDay();
        }

        return [
            'placement' => $placement,
            'category_id' => $categoryId,
            'month' => $month->format('Y-m'),
            'weeks' => $weeks,
        ];
    }

    private function weekAvailability(string $placement, ?int $categoryId, Carbon $date): array
    {
        $startAt = $date->copy()->setTime(11, 0, 0);
        $endAt = $startAt->copy()->addWeek()->subSecond();
        $reservedCount = $this->slotQuery->reservedCount($placement, $categoryId, $startAt);
        $remainingCount = max(0, HospitalEventAd::WEEKLY_SLOT_LIMIT - $reservedCount);
        $isPast = $date->copy()->startOfDay()->lessThanOrEqualTo(now()->startOfDay());

        return [
            'date' => $date->toDateString(),
            'start_at' => $startAt->toISOString(),
            'end_at' => $endAt->toISOString(),
            'reserved_count' => $reservedCount,
            'remaining_count' => $remainingCount,
            'slot_limit' => HospitalEventAd::WEEKLY_SLOT_LIMIT,
            'is_sold_out' => $isPast || $remainingCount <= 0,
            'is_past' => $isPast,
        ];
    }
}
