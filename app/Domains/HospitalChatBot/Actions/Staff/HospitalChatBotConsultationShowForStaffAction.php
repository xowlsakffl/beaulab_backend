<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotConsultationForStaffDto;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotConsultationForStaffQuery;

final class HospitalChatBotConsultationShowForStaffAction
{
    public function __construct(private readonly HospitalChatBotConsultationForStaffQuery $query) {}

    /** @return array<string, mixed> */
    public function execute(int $consultationId): array
    {
        return HospitalChatBotConsultationForStaffDto::withHistories(
            $this->query->find($consultationId),
            $this->query->histories($consultationId),
        );
    }
}
