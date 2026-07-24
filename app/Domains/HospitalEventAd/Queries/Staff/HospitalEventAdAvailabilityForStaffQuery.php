<?php

namespace App\Domains\HospitalEventAd\Queries\Staff;

use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalEventAd\Support\HospitalEventAdCalendarCache;
use App\Domains\HospitalEventAd\Support\HospitalEventAdSalesDeadline;
use Illuminate\Support\Carbon;

final class HospitalEventAdAvailabilityForStaffQuery
{
    public function __construct(
        private readonly HospitalEventAdSlotAvailabilityForStaffQuery $slotQuery,
        private readonly HospitalEventAdSalesDeadline $salesDeadline,
    ) {}

    public function get(string $placement, ?int $categoryId, Carbon $month): array
    {
        return HospitalEventAdCalendarCache::rememberAvailability(
            $placement,
            $categoryId,
            $month,
            fn (): array => $this->uncachedAvailability($placement, $categoryId, $month),
        );
    }

    private function uncachedAvailability(string $placement, ?int $categoryId, Carbon $month): array
    {
        $weeks = [];
        $cursor = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();
        $startDayOfWeek = HospitalEventAd::startDayOfWeek($placement);

        while ($cursor->lte($endOfMonth)) {
            if ($cursor->dayOfWeek === $startDayOfWeek) {
                $weeks[] = $this->weekAvailability($placement, $categoryId, $cursor);
            }

            $cursor->addDay();
        }

        return [
            'placement' => $placement,
            'category_id' => $categoryId,
            'month' => $month->format('Y-m'),
            'start_day_of_week' => $startDayOfWeek,
            'start_day_label' => HospitalEventAd::startDayLabel($placement),
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
        $isDeadlineClosed = $this->salesDeadline->isClosed($startAt);

        return [
            'date' => $date->toDateString(),
            'start_at' => $startAt->toISOString(),
            'end_at' => $endAt->toISOString(),
            'reserved_count' => $reservedCount,
            'remaining_count' => $remainingCount,
            'slot_limit' => HospitalEventAd::WEEKLY_SLOT_LIMIT,
            'is_sold_out' => $isPast || $isDeadlineClosed || $remainingCount <= 0,
            'is_past' => $isPast,
            'is_deadline_closed' => $isDeadlineClosed,
        ];
    }
}
