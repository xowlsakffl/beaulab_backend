<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Common\Authorization\AccessPermissions as P;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotConsultationForStaffDto;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotConsultationForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotStaffActor;

final class HospitalChatBotConsultationShowForStaffAction
{
    public function __construct(private readonly HospitalChatBotConsultationForStaffQuery $query) {}

    /** @return array<string, mixed> */
    public function execute(int $consultationId): array
    {
        HospitalChatBotStaffActor::authorize(P::BEAULAB_CHATBOT_CONSULTATION_SHOW);

        return HospitalChatBotConsultationForStaffDto::withHistories(
            $this->query->find($consultationId),
            $this->query->histories($consultationId),
        );
    }
}
