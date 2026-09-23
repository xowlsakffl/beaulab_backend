<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Events;

use App\Domains\HospitalChatBot\Dto\HospitalChatBotConsultationMessageDto;
use App\Domains\HospitalChatBot\Queries\HospitalChatBotConsultationMessageQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 새 상담 메시지를 상담 채널로 보낸다.
 * 채팅 체감 지연을 줄이기 위해 큐를 거치지 않고 즉시 발행한다.
 */
final class HospitalChatBotConsultationMessageCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $messageId,
        public readonly int $consultationId,
    ) {}

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(HospitalChatBotChannel::consultation($this->consultationId))];
    }

    public function broadcastAs(): string
    {
        return 'hospital-chatbot.message.created';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        $message = app(HospitalChatBotConsultationMessageQuery::class)->find($this->messageId);

        // 메시지가 사라졌다면 식별자만 보낸다. 수신 측이 다시 조회하면 된다.
        if ($message === null) {
            return ['consultation_id' => $this->consultationId, 'message_id' => $this->messageId];
        }

        return ['message' => HospitalChatBotConsultationMessageDto::fromModel($message)];
    }
}
