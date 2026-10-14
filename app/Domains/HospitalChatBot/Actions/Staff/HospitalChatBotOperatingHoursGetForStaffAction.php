<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Common\Authorization\AccessPermissions as P;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotOperatingHoursForStaffDto;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotOperatingHoursForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;
use App\Domains\HospitalChatBot\Support\HospitalChatBotStaffActor;

final class HospitalChatBotOperatingHoursGetForStaffAction
{
    public function __construct(
        private readonly HospitalChatBotOperatingHoursForStaffQuery $query,
        private readonly HospitalChatBotAvailabilityResolver $resolver,
    ) {}

    public function execute(): array
    {
        HospitalChatBotStaffActor::authorize(P::BEAULAB_CHATBOT_OPERATION_SHOW);

        return HospitalChatBotOperatingHoursForStaffDto::fromCollections(
            $this->query->hours(),
            $this->query->closures(),
            $this->resolver,
            $this->query->setting(),
        );
    }
}
