<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Dto\Staff;

use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationStatusHistory;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final readonly class HospitalChatBotConsultationForStaffDto
{
    /** @return array<string, mixed> */
    public static function fromModel(HospitalChatBotConsultation $consultation): array
    {
        return [
            'id' => (int) $consultation->id,
            'status' => (string) $consultation->status,
            'hospital' => [
                'id' => (int) $consultation->hospital_id,
                'name' => self::text($consultation->hospital_name_snapshot),
                'logo' => $consultation->relationLoaded('hospital') && $consultation->hospital?->logoMedia
                    ? ['path' => $consultation->hospital->logoMedia->publicPath()]
                    : null,
            ],
            'requester' => [
                'account_hospital_id' => $consultation->requested_by_account_hospital_id !== null
                    ? (int) $consultation->requested_by_account_hospital_id
                    : null,
                'name' => self::text($consultation->requester_name_snapshot),
            ],
            'assigned_staff' => [
                'id' => $consultation->assigned_staff_id !== null ? (int) $consultation->assigned_staff_id : null,
                'name' => self::text($consultation->assigned_staff_name_snapshot),
            ],
            'requested_at' => self::time($consultation->requested_at),
            'connected_at' => self::time($consultation->connected_at),
            'ended_at' => self::time($consultation->ended_at),
            'canceled_at' => self::time($consultation->canceled_at),
            'close_reason' => self::text($consultation->close_reason),
            'waiting_seconds' => self::waitingSeconds($consultation),
            'duration_seconds' => self::durationSeconds($consultation),
            'last_message_at' => self::time($consultation->last_message_at),
            'last_message' => $consultation->relationLoaded('latestMessage')
                ? self::text($consultation->latestMessage?->content)
                : null,
            'last_message_sequence' => (int) $consultation->last_message_sequence,
            'source_page_path' => self::text($consultation->source_page_path),
            'context_qna' => self::contextQna($consultation),
        ];
    }

    /**
     * @param  Collection<int, HospitalChatBotConsultationStatusHistory>  $histories
     * @return array<string, mixed>
     */
    public static function withHistories(HospitalChatBotConsultation $consultation, Collection $histories): array
    {
        return self::fromModel($consultation) + [
            'status_histories' => $histories->map(
                static fn (HospitalChatBotConsultationStatusHistory $history): array => [
                    'from_status' => self::text($history->from_status),
                    'to_status' => (string) $history->to_status,
                    'actor_type' => (string) $history->actor_type,
                    'actor_id' => $history->actor_id !== null ? (int) $history->actor_id : null,
                    'reason' => self::text($history->reason),
                    'occurred_at' => self::time($history->occurred_at),
                ],
            )->all(),
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

    /**
     * 대기 목록의 "얼마나 기다렸는지"에 쓴다.
     * 연결된 뒤에는 요청부터 연결까지 걸린 시간으로 고정된다.
     */
    private static function waitingSeconds(HospitalChatBotConsultation $consultation): ?int
    {
        $requestedAt = $consultation->requested_at;
        if (! $requestedAt instanceof CarbonInterface) {
            return null;
        }

        $until = match ((string) $consultation->status) {
            HospitalChatBotConsultationStatus::WAITING => CarbonImmutable::now(),
            HospitalChatBotConsultationStatus::CANCELED => $consultation->canceled_at,
            default => $consultation->connected_at,
        };

        if (! $until instanceof CarbonInterface) {
            return null;
        }

        return max(0, (int) $requestedAt->diffInSeconds($until, false));
    }

    /**
     * 연결된 뒤 실제로 대화한 시간.
     *
     * 연결되지 않고 취소된 상담은 값이 없다. 0초로 내보내면 "바로 끝난 상담"과
     * 구분되지 않는다.
     */
    private static function durationSeconds(HospitalChatBotConsultation $consultation): ?int
    {
        $connectedAt = $consultation->connected_at;
        if (! $connectedAt instanceof CarbonInterface) {
            return null;
        }

        $until = $consultation->ended_at instanceof CarbonInterface
            ? $consultation->ended_at
            // 진행 중이면 지금까지가 상담 시간이다.
            : ($consultation->status === HospitalChatBotConsultationStatus::CONNECTED ? CarbonImmutable::now() : null);

        if (! $until instanceof CarbonInterface) {
            return null;
        }

        return max(0, (int) $connectedAt->diffInSeconds($until, false));
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
