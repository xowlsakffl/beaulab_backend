<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Common;

use App\Domains\HospitalChatBot\Dto\HospitalChatBotConsultationMessageDto;
use App\Domains\HospitalChatBot\Jobs\BroadcastHospitalChatBotMessageJob;
use App\Domains\HospitalChatBot\Queries\HospitalChatBotConsultationMessageQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotLog;
use App\Domains\HospitalChatBot\Support\HospitalChatBotNotificationPublisher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 메시지 저장과 발행을 묶는다. 병의원 사용자와 상담원이 같은 경로를 쓴다.
 */
final class HospitalChatBotConsultationMessageSendAction
{
    public function __construct(
        private readonly HospitalChatBotConsultationMessageQuery $query,
        private readonly HospitalChatBotNotificationPublisher $notifications,
    ) {}

    /** @return array<string, mixed> */
    public function execute(
        int $consultationId,
        string $senderType,
        ?int $senderId,
        ?string $senderName,
        string $content,
        ?string $clientMessageId,
    ): array {
        $result = DB::transaction(function () use ($consultationId, $senderType, $senderId, $senderName, $content, $clientMessageId): array {
            $result = $this->query->send(
                consultationId: $consultationId,
                senderType: $senderType,
                senderId: $senderId,
                senderName: $senderName,
                content: $content,
                clientMessageId: $clientMessageId,
            );

            if ($result['created']) {
                $this->notifications->messageCreated($result['message']);
            }

            return $result;
        });

        // 미전달 메시지는 재요청 또는 정기 복구 작업에서 다시 발행한다.
        if ($result['message']->broadcast_pending) {
            try {
                BroadcastHospitalChatBotMessageJob::dispatch((int) $result['message']->id);
            } catch (Throwable $exception) {
                Log::warning('챗봇 메시지 전달 대기', [
                    'event' => HospitalChatBotLog::BROADCAST_FAILED,
                    'message_id' => (int) $result['message']->id,
                    'consultation_id' => $consultationId,
                    'exception_class' => $exception::class,
                ]);
            }
        }

        return HospitalChatBotConsultationMessageDto::fromModel($result['message']);
    }
}
