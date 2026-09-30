<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationTransition;
use App\Domains\HospitalChatBot\Support\HospitalChatBotLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        ?int $contextQnaId = null,
    ): HospitalChatBotConsultation {
        $hospitalId = (int) $account->hospital_id;

        $existing = $this->active($hospitalId);
        if ($existing !== null) {
            // 연타나 여러 탭에서 온 요청이 같은 상담으로 수렴한 경우다. 얼마나 잦은지 봐야 한다.
            Log::info('챗봇 상담 요청 중복 수렴', [
                'event' => HospitalChatBotLog::CONSULTATION_REQUEST_DEDUPED,
                'consultation_id' => (int) $existing->id,
                'hospital_id' => $hospitalId,
                'status' => (string) $existing->status,
            ]);

            return $existing;
        }

        $now = CarbonImmutable::now();
        $context = $this->contextQna($contextQnaId);

        try {
            // 상담과 최초 상태 이력은 함께 커밋한다. 이력만 실패하면 전이 기록이 없는 상담이 남는다.
            $consultation = DB::transaction(function () use (
                $account,
                $context,
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
                    'context_qna_id' => $context['id'],
                    'context_qna_question_snapshot' => $context['question'],
                    'context_qna_answer_snapshot' => $context['answer'],
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

            // 유니크 위반으로 수렴한 경우. 위의 사전 조회를 빠져나간 동시 요청이다.
            Log::info('챗봇 상담 요청 중복 수렴', [
                'event' => HospitalChatBotLog::CONSULTATION_REQUEST_DEDUPED,
                'consultation_id' => (int) $concurrent->id,
                'hospital_id' => $hospitalId,
                'via' => 'unique_violation',
            ]);

            return $concurrent;
        }

        Log::info('챗봇 상담 요청 생성', [
            'event' => HospitalChatBotLog::CONSULTATION_REQUESTED,
            'consultation_id' => (int) $consultation->id,
            'hospital_id' => $hospitalId,
            'account_hospital_id' => (int) $account->id,
            'context_qna_id' => $context['id'],
            'source_page_path' => $sourcePagePath,
        ]);

        return $consultation->fresh(['assignedStaff:id,name']) ?? $consultation;
    }

    /**
     * 상담을 부르기 직전에 보던 질문을 찾아 본문째 복사한다.
     *
     * Q&A는 고쳐지고 지워진다. 상담원이 봐야 하는 것은 "그때 무엇을 읽고 있었는지"라
     * 지금의 Q&A를 따라가면 안 된다. 찾지 못해도 상담은 그대로 연다. 맥락은 곁다리다.
     *
     * @return array{id:?int, question:?string, answer:?string}
     */
    private function contextQna(?int $qnaId): array
    {
        $empty = ['id' => null, 'question' => null, 'answer' => null];

        if ($qnaId === null) {
            return $empty;
        }

        $qna = HospitalChatBotQna::query()->find($qnaId);
        if ($qna === null) {
            return $empty;
        }

        return [
            'id' => (int) $qna->id,
            'question' => Str::limit((string) $qna->question, 497),
            'answer' => (string) $qna->answer,
        ];
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

    public function endByUser(HospitalChatBotConsultation $consultation, int $accountHospitalId): bool
    {
        return $this->transition->endByUser((int) $consultation->id, $accountHospitalId);
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        return ($exception->errorInfo[1] ?? null) === 1062;
    }
}
