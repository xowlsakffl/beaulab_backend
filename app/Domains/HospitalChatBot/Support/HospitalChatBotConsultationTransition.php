<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Support;

use App\Domains\HospitalChatBot\Events\HospitalChatBotConsultationChanged;
use App\Domains\HospitalChatBot\Events\HospitalChatBotQueueChanged;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationStatusHistory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     * 병원 사용자가 상담을 종료한다.
     *
     * 상담원 종료와 달리 담당자 조건이 없다. 대신 호출자가 상담의 소유 병원을 먼저 확인한다.
     */
    public function endByUser(int $consultationId, ?int $accountHospitalId): bool
    {
        $now = CarbonImmutable::now();

        return $this->transition(
            consultationId: $consultationId,
            fromStatus: HospitalChatBotConsultationStatus::CONNECTED,
            toStatus: HospitalChatBotConsultationStatus::ENDED,
            values: [
                'ended_at' => $now,
                'close_reason' => HospitalChatBotConsultationStatus::REASON_USER_ENDED,
                'active_hospital_id' => null,
            ],
            actorType: HospitalChatBotConsultationStatus::ACTOR_HOSPITAL_USER,
            actorId: $accountHospitalId,
            reason: HospitalChatBotConsultationStatus::REASON_USER_ENDED,
            occurredAt: $now,
        );
    }

    /**
     * 상담 생성 직후의 최초 이력을 남긴다. from_status는 비어 있다.
     */
    public function recordOpened(HospitalChatBotConsultation $consultation, ?int $actorId): void
    {
        $consultationId = (int) $consultation->id;

        // 새 요청이 상담원 대기 목록에 바로 뜨도록 알린다.
        DB::afterCommit(static function () use ($consultationId): void {
            self::announce($consultationId, HospitalChatBotConsultationStatus::WAITING);
        });

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
                /*
                 * 조건부 UPDATE가 0건이면 다른 쪽이 먼저 바꿔 놓은 것이다.
                 * Claim 충돌은 상담원 둘이 동시에 누른 경우로, 지표로 세야 한다(PRD 16.6).
                 */
                Log::warning('챗봇 상담 상태 전이 충돌', [
                    'event' => $toStatus === HospitalChatBotConsultationStatus::CONNECTED
                        ? HospitalChatBotLog::CONSULTATION_CLAIM_CONFLICT
                        : HospitalChatBotLog::CONSULTATION_CLOSE_CONFLICT,
                    'consultation_id' => $consultationId,
                    'from_status' => $fromStatus,
                    'to_status' => $toStatus,
                    'actor_type' => $actorType,
                    'actor_id' => $actorId,
                ]);

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

            Log::info('챗봇 상담 상태 전이', [
                'event' => $toStatus === HospitalChatBotConsultationStatus::CONNECTED
                    ? HospitalChatBotLog::CONSULTATION_CLAIMED
                    : HospitalChatBotLog::CONSULTATION_CLOSED,
                'consultation_id' => $consultationId,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'reason' => $reason,
            ]);

            // 커밋된 뒤에 발행한다. 롤백된 전이가 화면에 먼저 보이면 안 된다.
            DB::afterCommit(function () use ($consultationId, $toStatus): void {
                self::announce($consultationId, $toStatus);
            });

            return true;
        });
    }

    /**
     * 상태 변화를 상담 채널과 대기 목록 채널로 알린다.
     *
     * 발행이 실패해도 전이는 이미 커밋됐다. 양쪽 화면이 주기적으로 다시 조회하므로
     * 상태는 결국 맞춰진다. 메시지와 달리 재발행 장치를 두지 않는 이유다.
     */
    public static function announce(int $consultationId, string $status): void
    {
        HospitalChatBotConsultationChanged::dispatch($consultationId, $status);
        HospitalChatBotQueueChanged::dispatch($consultationId, $status);
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
