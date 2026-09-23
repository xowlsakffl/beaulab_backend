<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Hospital;

use App\Domains\HospitalChatBot\Actions\Shared\HospitalChatBotConsultationMessageListAction;
use App\Domains\HospitalChatBot\Actions\Shared\HospitalChatBotConsultationMessageSendAction;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage;
use App\Domains\HospitalChatBot\Queries\Hospital\HospitalChatBotConsultationForHospitalQuery;

/**
 * 병원 사용자의 메시지 조회·전송.
 * 어느 쪽이든 먼저 상담이 자기 병원 것인지 확인한다. 아니면 조회 단계에서 404가 난다.
 */
final class HospitalChatBotConsultationMessageForHospitalAction
{
    public function __construct(
        private readonly HospitalChatBotConsultationForHospitalQuery $query,
        private readonly HospitalChatBotConsultationMessageSendAction $send,
        private readonly HospitalChatBotConsultationMessageListAction $list,
    ) {}

    /** @return array{items: list<array<string, mixed>>, last_sequence: int} */
    public function index(int $consultationId, ?int $afterSequence, int $limit): array
    {
        $account = HospitalChatBotConsultationActiveForHospitalAction::account();
        $this->query->findForHospital($consultationId, (int) $account->hospital_id);

        return $this->list->execute($consultationId, $afterSequence, $limit);
    }

    /** @return array<string, mixed> */
    public function store(int $consultationId, string $content, ?string $clientMessageId): array
    {
        $account = HospitalChatBotConsultationActiveForHospitalAction::account();
        $this->query->findForHospital($consultationId, (int) $account->hospital_id);

        return $this->send->execute(
            consultationId: $consultationId,
            senderType: HospitalChatBotConsultationMessage::SENDER_HOSPITAL_USER,
            senderId: (int) $account->id,
            senderName: $account->name,
            content: $content,
            clientMessageId: $clientMessageId,
        );
    }
}
