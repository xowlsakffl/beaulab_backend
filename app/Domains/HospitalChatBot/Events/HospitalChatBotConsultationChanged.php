<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Events;

use App\Domains\HospitalChatBot\Dto\Hospital\HospitalChatBotConsultationForHospitalDto;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Support\HospitalChatBotChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 상담 상태가 바뀌었다. 상담 당사자(병의원 사용자와 담당 상담원)가 보는 채널로 보낸다.
 *
 * 병의원 사용자도 받는 채널이므로 상담원용 표현이 아니라 병의원용 표현을 싣는다.
 * 상담원 목록 갱신은 HospitalChatBotQueueChanged가 따로 담당한다.
 *
 * 이 이벤트를 놓쳐도 양쪽 화면이 주기적으로 다시 조회하므로 상태는 결국 맞춰진다.
 * 메시지와 달리 재발행 장치를 두지 않는 이유다.
 */
final class HospitalChatBotConsultationChanged implements ShouldBroadcastNow
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
        return [new PrivateChannel(HospitalChatBotChannel::consultation($this->consultationId))];
    }

    public function broadcastAs(): string
    {
        return 'hospital-chatbot.consultation.changed';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        $consultation = HospitalChatBotConsultation::query()->find($this->consultationId);

        if ($consultation === null) {
            return ['consultation_id' => $this->consultationId, 'status' => $this->status];
        }

        return ['consultation' => HospitalChatBotConsultationForHospitalDto::fromModel($consultation)];
    }
}
