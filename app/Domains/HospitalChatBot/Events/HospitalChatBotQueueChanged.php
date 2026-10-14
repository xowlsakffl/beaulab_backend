<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Events;

use App\Domains\HospitalChatBot\Support\HospitalChatBotChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 상담원 대기 목록이 바뀌었다. 상담원만 구독하는 채널로 보낸다.
 *
 * 병의원명·요청자·상태는 싣지 않는다. 조회 권한을 재검사하는 목록 API로 확인한다.
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
        return ['consultation_id' => $this->consultationId];
    }
}
