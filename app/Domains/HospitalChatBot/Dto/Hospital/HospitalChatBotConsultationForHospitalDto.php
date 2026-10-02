<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Dto\Hospital;

use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;
use Carbon\CarbonInterface;

final readonly class HospitalChatBotConsultationForHospitalDto
{
    /** @return array<string, mixed>|null */
    public static function fromModel(?HospitalChatBotConsultation $consultation): ?array
    {
        if ($consultation === null) {
            return null;
        }

        return [
            'id' => (int) $consultation->id,
            'status' => (string) $consultation->status,
            'requested_at' => self::time($consultation->requested_at),
            'connected_at' => self::time($consultation->connected_at),
            'ended_at' => self::time($consultation->ended_at),
            'canceled_at' => self::time($consultation->canceled_at),
            'close_reason' => $consultation->close_reason !== null ? (string) $consultation->close_reason : null,
            // 병의원 사용자에게는 담당 상담원의 표시명만 노출한다. 계정 ID는 내보내지 않는다.
            'assigned_staff_name' => $consultation->assigned_staff_name_snapshot !== null
                ? (string) $consultation->assigned_staff_name_snapshot
                : null,
            'last_message_sequence' => (int) $consultation->last_message_sequence,
            // 새로고침해도 "무엇을 보다가 불렀는지" 카드가 남아 있어야 한다.
            // SDK는 그 상태를 메모리에만 들고 있다.
            'context_qna' => self::contextQna($consultation),
        ];
    }

    /**
     * 상담을 부르기 직전에 보던 질문.
     *
     * 요청 당시 본문을 그대로 돌려준다. Q&A가 그 뒤 고쳐지거나 지워져도
     * 상담원이 보는 화면은 요청자가 읽던 그 글이어야 한다.
     *
     * @return array<string, mixed>|null
     */
    private static function contextQna(HospitalChatBotConsultation $consultation): ?array
    {
        $question = self::text($consultation->context_qna_question_snapshot);
        if ($question === null) {
            return null;
        }

        return [
            'id' => $consultation->context_qna_id !== null ? (int) $consultation->context_qna_id : null,
            'question' => $question,
            'answer' => self::text($consultation->context_qna_answer_snapshot),
        ];
    }

    private static function text(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function time(mixed $value): ?string
    {
        return $value instanceof CarbonInterface
            ? $value->setTimezone(HospitalChatBotAvailabilityResolver::TIMEZONE)->toIso8601String()
            : null;
    }
}
