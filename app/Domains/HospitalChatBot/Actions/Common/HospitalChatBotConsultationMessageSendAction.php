<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Common;

use App\Domains\HospitalChatBot\Dto\HospitalChatBotConsultationMessageDto;
use App\Domains\HospitalChatBot\Jobs\BroadcastHospitalChatBotMessageJob;
use App\Domains\HospitalChatBot\Queries\HospitalChatBotConsultationMessageQuery;

/**
 * 메시지 저장과 발행을 묶는다. 병원 사용자와 상담원이 같은 경로를 쓴다.
 */
final class HospitalChatBotConsultationMessageSendAction
{
    public function __construct(private readonly HospitalChatBotConsultationMessageQuery $query) {}

    /** @return array<string, mixed> */
    public function execute(
        int $consultationId,
        string $senderType,
        ?int $senderId,
        ?string $senderName,
        string $content,
        ?string $clientMessageId,
    ): array {
        $result = $this->query->send(
            consultationId: $consultationId,
            senderType: $senderType,
            senderId: $senderId,
            senderName: $senderName,
            content: $content,
            clientMessageId: $clientMessageId,
        );

        // 재시도로 걸러진 기존 메시지는 이미 한 번 발행됐다. 다시 보내지 않는다.
        if ($result['created']) {
            BroadcastHospitalChatBotMessageJob::dispatch((int) $result['message']->id);
        }

        return HospitalChatBotConsultationMessageDto::fromModel($result['message']);
    }
}
