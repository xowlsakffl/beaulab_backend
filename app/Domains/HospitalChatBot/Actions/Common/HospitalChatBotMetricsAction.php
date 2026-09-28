<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Common;

use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use App\Domains\HospitalChatBot\Support\HospitalChatBotLog;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

/**
 * 지금 이 순간의 챗봇 상태를 한 줄로 남긴다(PRD 16.6 권장 지표).
 *
 * 전용 메트릭 스택이 없으므로 구조화 로그로 대신한다. 수집기에서 event로 묶고
 * 필드를 그래프로 올리면 지표가 된다. 나중에 Prometheus 같은 것이 붙으면
 * 이 Action이 값을 만드는 자리는 그대로 두고 내보내는 곳만 바꾸면 된다.
 *
 * 세는 값은 "지금 상태"(gauge)다. 요청 수나 충돌 수 같은 누적값은 각 지점의
 * 이벤트 로그를 세는 쪽이 정확하다. 여기서 또 세면 두 숫자가 어긋난다.
 */
final class HospitalChatBotMetricsAction
{
    /** @return array<string, mixed> */
    public function execute(): array
    {
        $now = CarbonImmutable::now();

        $waiting = HospitalChatBotConsultation::query()
            ->where('status', HospitalChatBotConsultationStatus::WAITING);

        $oldestRequestedAt = (clone $waiting)->min('requested_at');

        /*
         * 발행이 꺼져 있으면 pending은 절대 줄지 않는다. 그 상태의 숫자를
         * 장애로 읽으면 안 되므로 지표에 함께 남긴다.
         */
        $broadcasting = (string) config('broadcasting.default');

        $metrics = [
            'broadcast_driver' => $broadcasting,
            'broadcast_enabled' => ! in_array($broadcasting, ['log', 'null'], true),
            'waiting_count' => (clone $waiting)->count(),
            'connected_count' => HospitalChatBotConsultation::query()
                ->where('status', HospitalChatBotConsultationStatus::CONNECTED)
                ->count(),
            'longest_waiting_seconds' => self::secondsSince($oldestRequestedAt, $now),
            // 발행되지 못하고 남은 메시지. 꾸준히 쌓이면 큐나 Reverb 쪽 문제다.
            'pending_broadcast_count' => HospitalChatBotConsultationMessage::query()
                ->where('broadcast_pending', true)
                ->count(),
            // 오래 남은 것이 있으면 재발행 커맨드가 못 따라잡고 있다는 뜻이다.
            'oldest_pending_broadcast_seconds' => self::secondsSince(
                HospitalChatBotConsultationMessage::query()->where('broadcast_pending', true)->min('created_at'),
                $now,
            ),
        ];

        Log::info('챗봇 지표', ['event' => HospitalChatBotLog::METRICS] + $metrics);

        return $metrics;
    }

    private static function secondsSince(mixed $from, CarbonInterface $now): ?int
    {
        if ($from === null || $from === '') {
            return null;
        }

        $parsed = $from instanceof CarbonInterface ? $from : CarbonImmutable::parse((string) $from);

        return max(0, (int) $parsed->diffInSeconds($now, false));
    }
}
