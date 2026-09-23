<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationTransition;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class HospitalChatBotConsultationForHospitalQuery
{
    public function __construct(
        private readonly HospitalChatBotConsultationTransition $transition,
    ) {}

    public function active(int $hospitalId): ?HospitalChatBotConsultation
    {
        return HospitalChatBotConsultation::query()
            ->with('assignedStaff:id,name')
            ->where('hospital_id', $hospitalId)
            ->active()
            ->orderByDesc('id')
            ->first();
    }

    public function findForHospital(int $consultationId, int $hospitalId): HospitalChatBotConsultation
    {
        $consultation = HospitalChatBotConsultation::query()
            ->with('assignedStaff:id,name')
            ->where('id', $consultationId)
            ->where('hospital_id', $hospitalId)
            ->first();

        if ($consultation === null) {
            throw new CustomException(ErrorCode::NOT_FOUND, '상담을 찾을 수 없습니다.');
        }

        return $consultation;
    }

    /**
     * 상담 요청을 만든다.
     *
     * 이미 활성 상담이 있으면 새로 만들지 않고 기존 상담을 돌려준다.
     * 연결 버튼 연타나 여러 탭에서의 동시 요청도 같은 상담 한 건으로 수렴한다(PRD EX-001, EX-002).
     */
    public function open(
        AccountHospital $account,
        ?string $sourceApplication,
        ?string $sourcePagePath,
    ): HospitalChatBotConsultation {
        $hospitalId = (int) $account->hospital_id;

        $existing = $this->active($hospitalId);
        if ($existing !== null) {
            return $existing;
        }

        $now = CarbonImmutable::now();

        try {
            // 상담과 최초 상태 이력은 함께 커밋한다. 이력만 실패하면 전이 기록이 없는 상담이 남는다.
            $consultation = DB::transaction(function () use (
                $account,
                $hospitalId,
                $now,
                $sourceApplication,
                $sourcePagePath,
            ): HospitalChatBotConsultation {
                $created = HospitalChatBotConsultation::query()->create([
                    'hospital_id' => $hospitalId,
                    'hospital_name_snapshot' => $account->hospital?->name,
                    'requested_by_account_hospital_id' => (int) $account->id,
                    'requester_name_snapshot' => $account->name,
                    'status' => HospitalChatBotConsultationStatus::WAITING,
                    'active_hospital_id' => $hospitalId,
                    'requested_at' => $now,
                    'last_message_sequence' => 0,
                    'source_application' => $sourceApplication,
                    'source_page_path' => $sourcePagePath,
                    'version' => 0,
                ]);

                $this->transition->recordOpened($created, (int) $account->id);

                return $created;
            });
        } catch (QueryException $exception) {
            // active_hospital_id 유니크 위반. 동시 요청이 먼저 상담을 열었다는 뜻이므로 그 상담을 돌려준다.
            if (! $this->isUniqueViolation($exception)) {
                throw $exception;
            }

            $concurrent = $this->active($hospitalId);
            if ($concurrent === null) {
                throw $exception;
            }

            return $concurrent;
        }

        return $consultation->fresh(['assignedStaff:id,name']) ?? $consultation;
    }

    public function cancel(HospitalChatBotConsultation $consultation, int $accountHospitalId): bool
    {
        return $this->transition->cancel(
            consultationId: (int) $consultation->id,
            actorType: HospitalChatBotConsultationStatus::ACTOR_HOSPITAL_USER,
            actorId: $accountHospitalId,
            reason: HospitalChatBotConsultationStatus::REASON_USER_CANCELED,
        );
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        return ($exception->errorInfo[1] ?? null) === 1062;
    }
}
