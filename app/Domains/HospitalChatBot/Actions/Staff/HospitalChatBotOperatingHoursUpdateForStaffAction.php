<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotOperatingHoursForStaffDto;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotOperatingHoursForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;
use Illuminate\Support\Facades\DB;

final class HospitalChatBotOperatingHoursUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalChatBotOperatingHoursForStaffQuery $query,
        private readonly HospitalChatBotAvailabilityResolver $resolver,
    ) {}

    public function execute(array $settings): array
    {
        $actor = auth()->user();
        $staffId = $actor instanceof AccountStaff ? (int) $actor->id : null;

        return DB::transaction(function () use ($settings, $staffId): array {
            $this->query->replace($settings['weekly_schedule'], $settings['closure_dates'], $staffId);
            $this->query->saveSetting($settings['is_force_closed'], $settings['force_closed_reason'], $staffId);

            return HospitalChatBotOperatingHoursForStaffDto::fromCollections(
                $this->query->hours(),
                $this->query->closures(),
                $this->resolver,
                $this->query->setting(),
            );
        });
    }
}
