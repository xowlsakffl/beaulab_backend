<?php

namespace App\Domains\HospitalPromotion\Queries;

use App\Domains\HospitalPromotion\Dto\HospitalPromotionAvailabilityDto;
use App\Domains\HospitalPromotion\Models\HospitalPromotion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class HospitalPromotionQuery
{
    public function listing(bool $withCreator = true): Builder
    {
        return HospitalPromotion::query()->select([
            'id', 'title', 'side', 'slot', 'start_date', 'end_date', 'status',
            'click_count', 'created_by_staff_id', 'created_at', 'updated_at',
        ])->with($withCreator ? ['banner', 'creator:id,name'] : ['banner']);
    }

    public function filter(Builder $query, array $filters): Builder
    {
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $query->where(function (Builder $query) use ($q): void {
                $query->where('title', 'like', '%'.$q.'%')
                    ->orWhereHas('creator', fn (Builder $creator) => $creator->where('name', 'like', '%'.$q.'%')->orWhere('nickname', 'like', '%'.$q.'%'));
                if (ctype_digit($q)) {
                    $query->orWhere('id', $q);
                }
            });
        }
        if (! empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }
        if (! empty($filters['start_date'])) {
            $query->where('end_date', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->where('start_date', '<=', $filters['end_date']);
        }

        return $query;
    }

    public function overlapping(array $schedule, ?int $excludeId = null): Builder
    {
        return HospitalPromotion::query()
            ->where('side', $schedule['side'])->where('slot', $schedule['slot'])
            ->where('start_date', '<=', $schedule['end_date'])
            ->where('end_date', '>=', $schedule['start_date'])
            ->when($excludeId !== null, fn (Builder $query) => $query->whereKeyNot($excludeId));
    }

    public function scheduleConflicts(array $schedule, ?int $excludeId = null): Builder
    {
        return $this->overlapping($schedule, $excludeId)
            ->select(['id', 'title', 'side', 'slot', 'start_date', 'end_date'])
            ->orderBy('start_date')->orderBy('id')
            ->limit(HospitalPromotionAvailabilityDto::LIMIT + 1);
    }

    /** Lock reference rows before the promotion; this also protects an empty slot. */
    public function lockSlots(array $slots): void
    {
        $ordered = [];
        foreach ($slots as $slot) {
            $ordered[$slot['side'].':'.(int) $slot['slot']] = $slot;
        }
        ksort($ordered, SORT_STRING);
        foreach ($ordered as $slot) {
            $lock = DB::table('hospital_promotion_slot_locks')
                ->where('side', $slot['side'])->where('slot', $slot['slot'])
                ->lockForUpdate()->first();
            if ($lock === null) {
                throw new \RuntimeException('Hospital promotion slot reference rows are missing.');
            }
        }
    }

    public function lockPromotion(int $id): HospitalPromotion
    {
        return HospitalPromotion::query()->lockForUpdate()->findOrFail($id);
    }
}
