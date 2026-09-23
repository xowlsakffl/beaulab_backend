<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Support;

use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationStatusHistory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * 상담 상태 전이를 한곳에서 수행한다.
 *
 * 모든 전이는 "현재 상태가 아직 기대한 값일 때만" 갱신하는 조건부 UPDATE로 처리한다.
 * 영향 행 수가 0이면 다른 요청이 먼저 상태를 바꾼 것이므로 호출자가 409로 응답한다(PRD 11.4).
 */
final class HospitalChatBotConsultationTransition
{
    /**
     * 대기 중인 상담을 상담원에게 연결한다.
     *
     * @return bool 연결에 성공하면 true. 이미 다른 상담원이 가져갔으면 false.
     */
    public function claim(int $consultationId, int $staffId, ?string $staffName): bool
    {
        $now = CarbonImmutable::now();

        return $this->transition(
            consultationId: $consultationId,
            fromStatus: HospitalChatBotConsultationStatus::WAITING,
            toStatus: HospitalChatBotConsultationStatus::CONNECTED,
            values: [
                'assigned_staff_id' => $staffId,
                'assigned_staff_name_snapshot' => $staffName,
                'connected_at' => $now,
            ],
            actorType: HospitalChatBotConsultationStatus::ACTOR_AGENT,
            actorId: $staffId,
            reason: null,
            occurredAt: $now,
        );
    }

    /**
     * 대기 중인 상담을 취소한다. 사용자 취소와 시스템 만료가 같은 경로를 쓴다.
     */
    public function cancel(int $consultationId, string $actorType, ?int $actorId, string $reason): bool
    {
        $now = CarbonImmutable::now();

        return $this->transition(
            consultationId: $consultationId,
            fromStatus: HospitalChatBotConsultationStatus::WAITING,
            toStatus: HospitalChatBotConsultationStatus::CANCELED,
            values: [
                'canceled_at' => $now,
                'close_reason' => $reason,
                // 활성 키를 비워야 같은 병원이 새 상담을 열 수 있다.
                'active_hospital_id' => null,
            ],
            actorType: $actorType,
            actorId: $actorId,
            reason: $reason,
            occurredAt: $now,
        );
    }

    /**
     * 연결된 상담을 종료한다.
     *
     * 담당 상담원 조건을 UPDATE의 WHERE에 함께 건다. 호출자가 먼저 읽어 둔 행으로만 판단하면
     * "읽을 때는 WAITING이었는데 그사이 다른 상담원이 연결한" 경우에 남의 상담을 종료할 수 있다.
     */
    public function end(int $consultationId, int $staffId): bool
    {
        $now = CarbonImmutable::now();

        return $this->transition(
            consultationId: $consultationId,
            fromStatus: HospitalChatBotConsultationStatus::CONNECTED,
            toStatus: HospitalChatBotConsultationStatus::ENDED,
            values: [
                'ended_at' => $now,
                'close_reason' => HospitalChatBotConsultationStatus::REASON_AGENT_COMPLETED,
                'active_hospital_id' => null,
            ],
            actorType: HospitalChatBotConsultationStatus::ACTOR_AGENT,
            actorId: $staffId,
            reason: HospitalChatBotConsultationStatus::REASON_AGENT_COMPLETED,
            occurredAt: $now,
            conditions: ['assigned_staff_id' => $staffId],
        );
    }

    /**
     * 상담 생성 직후의 최초 이력을 남긴다. from_status는 비어 있다.
     */
    public function recordOpened(HospitalChatBotConsultation $consultation, ?int $actorId): void
    {
        $this->record(
            consultationId: (int) $consultation->id,
            fromStatus: null,
            toStatus: HospitalChatBotConsultationStatus::WAITING,
            actorType: HospitalChatBotConsultationStatus::ACTOR_HOSPITAL_USER,
            actorId: $actorId,
            reason: null,
            occurredAt: CarbonImmutable::instance($consultation->requested_at ?? CarbonImmutable::now()),
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $conditions  상태 외에 UPDATE의 WHERE에 함께 걸 조건
     */
    private function transition(
        int $consultationId,
        string $fromStatus,
        string $toStatus,
        array $values,
        string $actorType,
        ?int $actorId,
        ?string $reason,
        CarbonImmutable $occurredAt,
        array $conditions = [],
    ): bool {
        return DB::transaction(function () use (
            $consultationId,
            $fromStatus,
            $toStatus,
            $values,
            $actorType,
            $actorId,
            $reason,
            $occurredAt,
            $conditions,
        ): bool {
            $affected = HospitalChatBotConsultation::query()
                ->where('id', $consultationId)
                ->where('status', $fromStatus)
                ->where($conditions)
                ->update(array_merge($values, [
                    'status' => $toStatus,
                    'version' => DB::raw('version + 1'),
                    'updated_at' => $occurredAt,
                ]));

            if ($affected !== 1) {
                return false;
            }

            $this->record(
                consultationId: $consultationId,
                fromStatus: $fromStatus,
                toStatus: $toStatus,
                actorType: $actorType,
                actorId: $actorId,
                reason: $reason,
                occurredAt: $occurredAt,
            );

            return true;
        });
    }

    private function record(
        int $consultationId,
        ?string $fromStatus,
        string $toStatus,
        string $actorType,
        ?int $actorId,
        ?string $reason,
        CarbonImmutable $occurredAt,
    ): void {
        HospitalChatBotConsultationStatusHistory::query()->create([
            'consultation_id' => $consultationId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'reason' => $reason,
            'occurred_at' => $occurredAt,
            'request_id' => self::requestId(),
        ]);
    }

    private static function requestId(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        $value = app('request')->attributes->get('traceId');

        return is_string($value) ? $value : null;
    }
}
