<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotOperatingHoursForStaffDto;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotOperatingHoursForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;

final class HospitalChatBotOperatingHoursGetForStaffAction
{
    public function __construct(
        private readonly HospitalChatBotOperatingHoursForStaffQuery $query,
        private readonly HospitalChatBotAvailabilityResolver $resolver,
    ) {}

    public function execute(): array
    {
        return HospitalChatBotOperatingHoursForStaffDto::fromCollections(
            $this->query->hours(),
            $this->query->closures(),
            $this->resolver,
        );
    }
}
