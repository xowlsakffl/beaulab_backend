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
use Throwable;

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

            /*
             * 발행과 로그 모두 커밋된 뒤에 한다.
             *
             * 롤백된 전이가 화면에 먼저 보이면 안 되고, 로그도 마찬가지다. 커밋이
             * 실패하면 상태는 되돌아가는데 로그만 성공으로 남아 집계가 실제보다 커진다.
             *
             * 로그가 먼저다. 발행은 동기(ShouldBroadcastNow)라 Reverb가 안 닿으면
             * 여기서 던지는데, 로그가 뒤에 있으면 그 순간의 전이가 로그에서 사라진다.
             */
            DB::afterCommit(function () use (
                $consultationId,
                $fromStatus,
                $toStatus,
                $actorType,
                $actorId,
                $reason,
            ): void {
                Log::info(self::logMessage($toStatus, $reason), [
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

                self::announce($consultationId, $toStatus);
            });

            return true;
        });
    }

    /**
     * 로그 문장.
     *
     * 저장소의 다른 로그처럼 문장만 봐도 무슨 일인지 알아야 한다. event는 집계용이고
     * 사람은 laravel.log를 눈으로 훑는다. 종료 사유의 이름은 CSV와 같은 표를 쓴다.
     * 표가 둘이면 사유가 하나 늘 때 한쪽을 놓친다.
     */
    private static function logMessage(string $toStatus, ?string $reason): string
    {
        if ($toStatus === HospitalChatBotConsultationStatus::CONNECTED) {
            return '챗봇 상담 연결';
        }

        return '챗봇 상담 '.HospitalChatBotConsultationStatus::closeReasonLabel($reason);
    }

    /**
     * 상태 변화를 상담 채널과 대기 목록 채널로 알린다.
     *
     * 발행이 실패해도 전이는 이미 커밋됐다. 양쪽 화면이 주기적으로 다시 조회하므로
     * 상태는 결국 맞춰진다. 메시지와 달리 재발행 장치를 두지 않는 이유다.
     *
     * 그래서 예외를 삼킨다. 발행은 동기(ShouldBroadcastNow)라 Reverb가 죽으면 여기서
     * 던지는데, 그대로 두면 커밋된 전이가 500으로 나간다. 운영은 기존 채팅 때문에
     * Reverb가 켜져 있어 이 경로가 실제로 밟힌다. 기존 채팅의 발행 실패 처리와 같다.
     */
    public static function announce(int $consultationId, string $status): void
    {
        try {
            HospitalChatBotConsultationChanged::dispatch($consultationId, $status);
            HospitalChatBotQueueChanged::dispatch($consultationId, $status);
        } catch (Throwable $exception) {
            Log::warning('챗봇 상담 상태 발행 실패. 화면은 폴링으로 맞춰진다', [
                'event' => HospitalChatBotLog::CONSULTATION_ANNOUNCE_FAILED,
                'consultation_id' => $consultationId,
                'status' => $status,
                'exception' => $exception::class,
                'reason' => $exception->getMessage(),
            ]);
        }
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
