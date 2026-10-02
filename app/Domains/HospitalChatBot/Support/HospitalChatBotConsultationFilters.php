<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Support;

use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;

/**
 * 상담 목록과 내보내기가 함께 쓰는 필터.
 *
 * PRD는 "내보내기는 목록의 현재 필터 조건을 기준으로 한다"고 못 박는다(ADM-S06).
 * 두 요청이 각자 파싱하면 언젠가 갈라지고, 그러면 화면에서 본 것과 받은 파일이
 * 달라진다. 파싱을 한 곳에 두고 양쪽이 같은 배열을 만든다.
 */
final class HospitalChatBotConsultationFilters
{
    /** 집어야 할 순서. 대기가 먼저, 그 안에서는 오래 기다린 순. */
    public const SORT_QUEUE = 'queue';

    /** 기록 조회 기본값. 최신 요청 시각 내림차순(ADM-S05). */
    public const SORT_RECENT = 'recent';

    /**
     * 목록과 내보내기가 함께 쓰는 검증 규칙.
     *
     * 상속으로 묶을 수도 있지만 이 저장소의 FormRequest는 전부 final이다.
     * 규칙을 값으로 내주면 양쪽이 자기 규칙을 덧붙이면서도 한 곳만 고치면 된다.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:191'],
            'mine' => ['nullable', 'boolean'],
            'close_reason' => ['nullable', Rule::in(['AGENT_COMPLETED', 'USER_ENDED', 'USER_CANCELED', 'WAITING_TIMEOUT'])],
            'status' => ['nullable', 'array'],
            'status.*' => ['string', Rule::in([
                HospitalChatBotConsultationStatus::WAITING,
                HospitalChatBotConsultationStatus::CONNECTED,
                HospitalChatBotConsultationStatus::ENDED,
                HospitalChatBotConsultationStatus::CANCELED,
            ])],
            'assigned_staff_id' => ['nullable', 'integer', 'min:1'],
            'hospital_id' => ['nullable', 'integer', 'min:1'],
            'consultation_id' => ['nullable', 'integer', 'min:1'],
            // 요청자 표시명으로 찾는다. 계정 ID는 화면에서 알기 어렵다.
            'requester' => ['nullable', 'string', 'max:191'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            /*
             * 대기 목록과 기록 조회는 같은 데이터를 다른 순서로 본다.
             * 대기 목록은 집어야 할 순서(대기 먼저, 오래 기다린 순), 기록은 최신순이다.
             * 화면이 정하게 두지 않으면 한쪽은 반드시 뒤집힌 목록을 보게 된다.
             */
            'sort' => ['nullable', Rule::in([self::SORT_QUEUE, self::SORT_RECENT, 'manage'])],
        ];
    }

    /**
     * `?status=WAITING,CONNECTED` 를 배열로 편다.
     *
     * 쿼리스트링에 배열을 넣는 방식이 저장소마다 다르고, 화면은 쉼표로 붙여 보낸다.
     */
    public static function splitStatus(mixed $status): mixed
    {
        if (! is_string($status)) {
            return $status;
        }

        return array_values(array_filter(array_map('trim', explode(',', $status))));
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function fromValidated(array $validated): array
    {
        // 값이 비어 있으면(`?status=`) 필터를 주지 않은 것으로 본다.
        // 빈 배열을 그대로 넘기면 whereIn이 빠져 종료·취소까지 전부 나온다.
        $status = $validated['status'] ?? [];

        return [
            /*
             * 상태를 고르지 않으면 대기·진행만 보여준다. 기록 화면은 언제나
             * 상태를 명시해 보내므로 이 기본값에 걸리지 않는다.
             */
            'status' => is_array($status) && $status !== [] ? array_values($status) : HospitalChatBotConsultationStatus::active(),
            'assigned_staff_id' => self::id($validated['assigned_staff_id'] ?? null),
            'mine_staff_id' => ! empty($validated['mine']) ? (int) auth()->id() : null,
            'q' => self::text($validated['q'] ?? null),
            'close_reason' => self::text($validated['close_reason'] ?? null),
            'hospital_id' => self::id($validated['hospital_id'] ?? null),
            'consultation_id' => self::id($validated['consultation_id'] ?? null),
            'requester' => self::text($validated['requester'] ?? null),
            'from' => self::startOfDay($validated['from'] ?? null),
            'to' => self::endOfDay($validated['to'] ?? null),
            'sort' => $validated['sort'] ?? self::SORT_QUEUE,
        ];
    }

    private static function id(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * 날짜만 받아 그 날의 처음과 끝으로 넓힌다.
     *
     * 화면은 날짜를 고르지 시각을 고르지 않는다. to를 그대로 쓰면 00:00이 되어
     * 마지막 날 하루가 통째로 빠진다.
     */
    private static function startOfDay(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && $value !== ''
            ? CarbonImmutable::parse($value, HospitalChatBotAvailabilityResolver::TIMEZONE)->startOfDay()
            : null;
    }

    private static function endOfDay(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && $value !== ''
            ? CarbonImmutable::parse($value, HospitalChatBotAvailabilityResolver::TIMEZONE)->endOfDay()
            : null;
    }
}
