<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Staff;

use App\Domains\HospitalChatBot\Models\HospitalChatBotClosureDate;
use App\Domains\HospitalChatBot\Models\HospitalChatBotOperatingHour;
use Illuminate\Support\Collection;

final class HospitalChatBotOperatingHoursForStaffQuery
{
    /** @return Collection<int, HospitalChatBotOperatingHour> */
    public function hours(): Collection
    {
        return HospitalChatBotOperatingHour::query()->orderBy('day_of_week')->get();
    }

    /** @return Collection<int, HospitalChatBotClosureDate> */
    public function closures(): Collection
    {
        return HospitalChatBotClosureDate::query()->orderBy('closure_date')->get();
    }

    /**
     * @param  list<array{day_of_week:int,is_open:bool,opens_at:?string,closes_at:?string}>  $weeklySchedule
     * @param  list<array{date:string,reason:?string}>  $closureDates
     */
    public function replace(array $weeklySchedule, array $closureDates, ?int $staffId): void
    {
        foreach ($weeklySchedule as $item) {
            HospitalChatBotOperatingHour::query()->updateOrCreate(
                ['day_of_week' => $item['day_of_week']],
                [
                    'is_open' => $item['is_open'],
                    'opens_at' => $item['opens_at'],
                    'closes_at' => $item['closes_at'],
                    'updated_by_staff_id' => $staffId,
                ],
            );
        }

        $dates = array_column($closureDates, 'date');
        $obsolete = HospitalChatBotClosureDate::query()
            ->when($dates !== [], static fn ($query) => $query->whereNotIn('closure_date', $dates))
            ->get();
        $obsolete->each->delete();

        foreach ($closureDates as $item) {
            HospitalChatBotClosureDate::query()->updateOrCreate(
                ['closure_date' => $item['date']],
                [
                    'reason' => $item['reason'],
                    'updated_by_staff_id' => $staffId,
                ],
            );
        }
    }
}
