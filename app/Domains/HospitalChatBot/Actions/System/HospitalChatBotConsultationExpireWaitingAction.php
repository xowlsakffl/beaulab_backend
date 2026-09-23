<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\System;

use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationTransition;
use Carbon\CarbonImmutable;

/**
 * 상담원이 가져가지 않은 대기 상담을 취소한다.
 * 취소도 조건부 UPDATE를 거치므로, 만료 직전에 상담원이 연결했다면 그 건은 건너뛴다.
 */
final class HospitalChatBotConsultationExpireWaitingAction
{
    public function __construct(
        private readonly HospitalChatBotConsultationTransition $transition,
    ) {}

    public function execute(int $timeoutMinutes, int $limit): int
    {
        $threshold = CarbonImmutable::now()->subMinutes($timeoutMinutes);

        $ids = HospitalChatBotConsultation::query()
            ->where('status', HospitalChatBotConsultationStatus::WAITING)
            ->where('requested_at', '<=', $threshold)
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $expired = 0;

        foreach ($ids as $id) {
            $canceled = $this->transition->cancel(
                consultationId: (int) $id,
                actorType: HospitalChatBotConsultationStatus::ACTOR_SYSTEM,
                actorId: null,
                reason: HospitalChatBotConsultationStatus::REASON_WAITING_TIMEOUT,
            );

            if ($canceled) {
                $expired++;
            }
        }

        return $expired;
    }
}
