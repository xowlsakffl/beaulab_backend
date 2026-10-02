<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotConsultationForStaffQuery;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotConsultationReadForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotStaffActor;

final class HospitalChatBotConsultationReadForStaffAction
{
    public function __construct(
        private readonly HospitalChatBotConsultationForStaffQuery $consultations,
        private readonly HospitalChatBotConsultationReadForStaffQuery $reads,
    ) {}

    public function execute(int $id, int $sequence): array
    {
        $consultation = $this->consultations->find($id);
        $this->reads->mark($id, (int) HospitalChatBotStaffActor::current()->id, min($sequence, (int) $consultation->last_message_sequence));

        return ['unread_count' => (int) ($this->reads->counts([$id], (int) HospitalChatBotStaffActor::current()->id)[$id] ?? 0)];
    }
}
