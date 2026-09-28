<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Support;

/**
 * 챗봇 도메인 로그의 event 키(PRD 16.6).
 *
 * 저장소는 `Log::info('한국어 문장', [...컨텍스트])` 로 로그를 남긴다. 그 형식을
 * 그대로 쓰되 컨텍스트에 event 키를 하나 더 얹는다. 수집기에서 event로 묶으면
 * 문장을 고쳐도 집계가 끊기지 않는다.
 *
 * 문자열을 호출부에 직접 쓰면 오타 하나로 그 이벤트만 집계에서 빠진다.
 */
final class HospitalChatBotLog
{
    public const CONSULTATION_REQUESTED = 'hospital_chatbot.consultation.requested';

    public const CONSULTATION_REQUEST_DEDUPED = 'hospital_chatbot.consultation.request_deduped';

    public const CONSULTATION_CLAIMED = 'hospital_chatbot.consultation.claimed';

    public const CONSULTATION_CLAIM_CONFLICT = 'hospital_chatbot.consultation.claim_conflict';

    public const CONSULTATION_CLOSED = 'hospital_chatbot.consultation.closed';

    public const CONSULTATION_CLOSE_CONFLICT = 'hospital_chatbot.consultation.close_conflict';

    public const MESSAGE_STORE_FAILED = 'hospital_chatbot.message.store_failed';

    public const BROADCAST_FAILED = 'hospital_chatbot.broadcast.failed';

    public const BROADCAST_RETRIED = 'hospital_chatbot.broadcast.retried';

    public const EXPORT_FAILED = 'hospital_chatbot.export.failed';

    public const OPERATING_HOURS_FAILED = 'hospital_chatbot.operating_hours.failed';

    public const METRICS = 'hospital_chatbot.metrics';
}
