<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Hospital;

use App\Domains\HospitalChatBot\Models\HospitalChatBotClosureDate;
use App\Domains\HospitalChatBot\Models\HospitalChatBotOperatingHour;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;
use Illuminate\Support\Collection;

final class HospitalChatBotAvailabilityForHospitalQuery
{
    /** @return Collection<int, HospitalChatBotOperatingHour> */
    public function hours(): Collection
    {
        return HospitalChatBotOperatingHour::query()->orderBy('day_of_week')->get();
    }

    /** @return Collection<int, HospitalChatBotClosureDate> */
    public function closures(): Collection
    {
        return HospitalChatBotClosureDate::query()
            ->whereDate('closure_date', '>=', now(HospitalChatBotAvailabilityResolver::TIMEZONE)->toDateString())
            ->orderBy('closure_date')
            ->get();
    }
}
