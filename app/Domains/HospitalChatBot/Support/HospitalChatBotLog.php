<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 챗봇 도메인 로그를 한 모양으로 남긴다(PRD 16.6).
 *
 * 메시지 문장을 자유롭게 쓰면 나중에 집계할 수 없다. event 키를 고정해 두고
 * 나머지는 컨텍스트로 넘긴다. 로그 수집기에서 `event` 로 묶으면 그대로 지표가 된다.
 *
 * RequestId 미들웨어가 traceId를 컨텍스트에 심어 두므로 한 요청에서 나온 줄들은
 * 따라갈 수 있다. 여기서 다시 넣지 않는다.
 */
final class HospitalChatBotLog
{
    /** 필수 로그의 event 이름(PRD 16.6). 문자열을 직접 쓰지 않게 상수로 둔다. */
    public const CONSULTATION_REQUESTED = 'hospital_chatbot.consultation.requested';

    public const CONSULTATION_REQUEST_DEDUPED = 'hospital_chatbot.consultation.request_deduped';

    public const CONSULTATION_CLAIMED = 'hospital_chatbot.consultation.claimed';

    public const CONSULTATION_CLAIM_CONFLICT = 'hospital_chatbot.consultation.claim_conflict';

    public const CONSULTATION_CLOSED = 'hospital_chatbot.consultation.closed';

    public const MESSAGE_STORE_FAILED = 'hospital_chatbot.message.store_failed';

    public const BROADCAST_FAILED = 'hospital_chatbot.broadcast.failed';

    public const BROADCAST_RETRIED = 'hospital_chatbot.broadcast.retried';

    public const BROADCAST_ABANDONED = 'hospital_chatbot.broadcast.abandoned';

    public const EXPORT_FAILED = 'hospital_chatbot.export.failed';

    public const OPERATING_HOURS_FAILED = 'hospital_chatbot.operating_hours.failed';

    public const METRICS = 'hospital_chatbot.metrics';

    /**
     * 평소에 일어나는 일.
     *
     * @param  array<string, mixed>  $context
     */
    public static function info(string $event, array $context = []): void
    {
        Log::info($event, ['event' => $event] + $context);
    }

    /**
     * 일어날 수 있지만 눈에 담아 둘 일. 충돌이나 재시도처럼 실패는 아닌 것들.
     *
     * @param  array<string, mixed>  $context
     */
    public static function warning(string $event, array $context = []): void
    {
        Log::warning($event, ['event' => $event] + $context);
    }

    /**
     * 사람이 봐야 하는 일. Alert Hook은 이 레벨을 본다.
     *
     * 예외는 클래스와 메시지만 남긴다. 스택 전체를 컨텍스트에 넣으면 한 줄이
     * 수백 줄이 되어 정작 event로 묶어 보기 어려워진다. 스택은 Laravel의
     * 기본 예외 로그가 이미 남긴다.
     *
     * @param  array<string, mixed>  $context
     */
    public static function failure(string $event, ?Throwable $exception = null, array $context = []): void
    {
        if ($exception !== null) {
            $context += [
                'exception' => $exception::class,
                'reason' => $exception->getMessage(),
            ];
        }

        Log::error($event, ['event' => $event] + $context);
    }
}
