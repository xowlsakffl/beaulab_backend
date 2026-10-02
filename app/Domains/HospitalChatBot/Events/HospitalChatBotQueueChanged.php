<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Events;

use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotConsultationForStaffDto;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Support\HospitalChatBotChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 상담원 대기 목록이 바뀌었다. 상담원만 구독하는 채널로 보낸다.
 *
 * 병의원명·요청자 등 상담원에게만 필요한 항목이 실리므로 상담 채널에는 보내지 않는다.
 */
final class HospitalChatBotQueueChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $consultationId,
        public readonly string $status,
    ) {}

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(HospitalChatBotChannel::queue())];
    }

    public function broadcastAs(): string
    {
        return 'hospital-chatbot.queue.changed';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        $consultation = HospitalChatBotConsultation::query()->find($this->consultationId);

        if ($consultation === null) {
            return ['consultation_id' => $this->consultationId, 'status' => $this->status];
        }

        return ['consultation' => HospitalChatBotConsultationForStaffDto::fromModel($consultation)];
    }
}
