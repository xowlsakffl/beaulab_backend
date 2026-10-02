<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalChatBot\Actions\Common\HospitalChatBotConsultationMessageListAction;
use App\Domains\HospitalChatBot\Actions\Common\HospitalChatBotConsultationMessageSendAction;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotConsultationForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotStaffActor;

/**
 * 상담원의 메시지 조회·전송.
 *
 * 조회는 상담원 누구나 할 수 있다. 전송은 담당 상담원만 가능하다(PRD 11.4-5).
 */
final class HospitalChatBotConsultationMessageForStaffAction
{
    public function __construct(
        private readonly HospitalChatBotConsultationForStaffQuery $query,
        private readonly HospitalChatBotConsultationMessageSendAction $send,
        private readonly HospitalChatBotConsultationMessageListAction $list,
    ) {}

    /** @return array{items: list<array<string, mixed>>, last_sequence: int} */
    public function index(int $consultationId, ?int $afterSequence, int $limit, ?int $beforeSequence = null): array
    {
        $this->query->find($consultationId);

        return $this->list->execute($consultationId, $afterSequence, $limit, $beforeSequence);
    }

    /** @return array<string, mixed> */
    public function store(int $consultationId, string $content, ?string $clientMessageId): array
    {
        $staff = HospitalChatBotStaffActor::current();
        $consultation = $this->query->find($consultationId);

        if ((int) $consultation->assigned_staff_id !== (int) $staff->id) {
            throw new CustomException(ErrorCode::FORBIDDEN, '담당 상담원만 메시지를 보낼 수 있습니다.');
        }

        return $this->send->execute(
            consultationId: $consultationId,
            senderType: HospitalChatBotConsultationMessage::SENDER_AGENT,
            senderId: (int) $staff->id,
            senderName: $staff->name,
            content: $content,
            clientMessageId: $clientMessageId,
        );
    }
}
