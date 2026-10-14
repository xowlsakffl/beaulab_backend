<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Common\Authorization\AccessPermissions as P;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotOperatingHoursForStaffDto;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotOperatingHoursForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;
use App\Domains\HospitalChatBot\Support\HospitalChatBotStaffActor;
use Illuminate\Support\Facades\DB;

final class HospitalChatBotOperatingHoursUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalChatBotOperatingHoursForStaffQuery $query,
        private readonly HospitalChatBotAvailabilityResolver $resolver,
    ) {}

    public function execute(array $settings): array
    {
        $staffId = (int) HospitalChatBotStaffActor::authorize(P::BEAULAB_CHATBOT_OPERATION_SHOW, P::BEAULAB_CHATBOT_OPERATION_UPDATE)->id;

        return DB::transaction(function () use ($settings, $staffId): array {
            $this->query->saveSetting($settings['is_force_closed'], $settings['force_closed_reason'], $staffId, $settings['public_holiday_codes'] ?? null, $settings['lunch'] ?? null);
            $this->query->replace($settings['weekly_schedule'], $settings['closure_dates'], $staffId);

            return HospitalChatBotOperatingHoursForStaffDto::fromCollections(
                $this->query->hours(),
                $this->query->closures(),
                $this->resolver,
                $this->query->setting(),
            );
        });
    }
}
