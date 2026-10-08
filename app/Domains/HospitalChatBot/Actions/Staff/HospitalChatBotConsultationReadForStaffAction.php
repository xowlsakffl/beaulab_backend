<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotConsultationForStaffQuery;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotConsultationReadForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotNotificationPublisher;
use App\Domains\HospitalChatBot\Support\HospitalChatBotStaffActor;
use Illuminate\Support\Facades\DB;

final class HospitalChatBotConsultationReadForStaffAction
{
    public function __construct(
        private readonly HospitalChatBotConsultationForStaffQuery $consultations,
        private readonly HospitalChatBotConsultationReadForStaffQuery $reads,
        private readonly HospitalChatBotNotificationPublisher $notifications,
    ) {}

    public function execute(int $id, int $sequence): array
    {
        $staffId = (int) HospitalChatBotStaffActor::current()->id;
        $this->consultations->find($id);

        return DB::transaction(function () use ($id, $sequence, $staffId): array {
            $consultation = HospitalChatBotConsultation::query()->lockForUpdate()->findOrFail($id);
            $this->reads->mark($id, $staffId, min($sequence, (int) $consultation->last_message_sequence));
            $this->notifications->synchronizeRead($staffId, $id);

            return ['unread_count' => (int) ($this->reads->counts([$id], $staffId)[$id] ?? 0)];
        });
    }
}
