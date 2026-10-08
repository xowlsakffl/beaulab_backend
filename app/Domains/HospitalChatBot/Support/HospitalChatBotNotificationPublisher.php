<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Support;

use App\Domains\Common\Notification\Actions\StaffNotificationPublisher;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage;
use Illuminate\Support\Facades\DB;

final class HospitalChatBotNotificationPublisher
{
    public function __construct(private readonly StaffNotificationPublisher $publisher) {}

    public function requested(HospitalChatBotConsultation $consultation): void
    {
        $this->publisher->record(
            eventType: 'staff.hospital_chatbot.requested',
            eventKey: 'hospital-chatbot:requested:'.$consultation->id,
            targetType: 'hospital_chatbot_consultation',
            targetId: (int) $consultation->id,
            payload: $this->metadata($consultation),
        );
    }

    public function messageCreated(HospitalChatBotConsultationMessage $message): void
    {
        if ($message->sender_type !== HospitalChatBotConsultationMessage::SENDER_HOSPITAL_USER) {
            return;
        }

        $consultation = $message->consultation;
        if ($consultation->assigned_staff_id === null) {
            return;
        }

        $this->publisher->record(
            eventType: 'staff.hospital_chatbot.message',
            eventKey: 'hospital-chatbot:message:'.$message->id,
            targetType: 'hospital_chatbot_consultation',
            targetId: (int) $consultation->id,
            payload: [...$this->metadata($consultation), 'message_id' => (int) $message->id],
            recipientId: (int) $consultation->assigned_staff_id,
        );
    }

    public function synchronizeRead(int $staffId, int $consultationId): void
    {
        if (! HospitalChatBotConsultation::query()->whereKey($consultationId)->where('assigned_staff_id', $staffId)->exists()) {
            return;
        }

        // 상담 읽음은 순번, 알림 읽음은 메시지 PK를 쓰므로 저장된 커서를 변환한다.
        $sequence = (int) DB::table('hospital_chatbot_consultation_reads')
            ->where('consultation_id', $consultationId)
            ->where('staff_id', $staffId)
            ->value('last_read_sequence');
        $messageId = HospitalChatBotConsultationMessage::query()
            ->where('consultation_id', $consultationId)
            ->where('sender_type', HospitalChatBotConsultationMessage::SENDER_HOSPITAL_USER)
            ->where('sequence', '<=', $sequence)
            ->max('id');

        if ($messageId !== null) {
            $this->publisher->markConversationRead($staffId, $consultationId, (int) $messageId);
        }
    }

    private function metadata(HospitalChatBotConsultation $consultation): array
    {
        return array_filter([
            'hospital_id' => (int) $consultation->hospital_id,
            'hospital_name' => $consultation->hospital_name_snapshot,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
