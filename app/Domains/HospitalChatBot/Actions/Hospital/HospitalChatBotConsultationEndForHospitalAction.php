<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalChatBot\Dto\Hospital\HospitalChatBotConsultationForHospitalDto;
use App\Domains\HospitalChatBot\Queries\Hospital\HospitalChatBotConsultationForHospitalQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;

/**
 * 병원 사용자가 상담 중에 직접 종료한다.
 *
 * PRD D-09는 원래 상담원만 종료하도록 정했으나, 자기가 시작한 대화를 끝내지 못하는 것이
 * 부자연스러워 결정을 바꿨다. 대기 중 취소는 cancel이 따로 담당한다.
 */
final class HospitalChatBotConsultationEndForHospitalAction
{
    public function __construct(
        private readonly HospitalChatBotConsultationForHospitalQuery $query,
        private readonly HospitalChatBotAvailabilityForHospitalAction $availability,
    ) {}

    /** @return array<string, mixed> */
    public function execute(int $consultationId): array
    {
        $account = HospitalChatBotConsultationActiveForHospitalAction::account();

        // 다른 병원의 상담이면 여기서 404가 난다.
        $consultation = $this->query->findForHospital($consultationId, (int) $account->hospital_id);

        if ($this->query->endByUser($consultation, (int) $account->id)) {
            return $this->payload($consultationId, (int) $account->hospital_id);
        }

        $current = $consultation->fresh(['assignedStaff:id,name']) ?? $consultation;

        // 상담원이 먼저 종료했어도 결과는 같다. 재시도가 안전해야 한다.
        if ((string) $current->status === HospitalChatBotConsultationStatus::ENDED) {
            return [
                'availability' => $this->availability->execute(),
                'consultation' => HospitalChatBotConsultationForHospitalDto::fromModel($current),
            ];
        }

        // 아직 대기 중이면 종료가 아니라 취소를 써야 한다.
        throw new CustomException(
            ErrorCode::CONSULTATION_NOT_ACTIVE,
            (string) $current->status === HospitalChatBotConsultationStatus::WAITING
                ? '아직 상담원과 연결되지 않았습니다. 대기 중에는 요청 취소를 사용해 주세요.'
                : '이미 종료되었거나 취소된 상담입니다.',
            ['consultation' => HospitalChatBotConsultationForHospitalDto::fromModel($current)],
        );
    }

    /** @return array<string, mixed> */
    private function payload(int $consultationId, int $hospitalId): array
    {
        $ended = $this->query->findForHospital($consultationId, $hospitalId);

        return [
            'availability' => $this->availability->execute(),
            'consultation' => HospitalChatBotConsultationForHospitalDto::fromModel($ended),
        ];
    }
}
