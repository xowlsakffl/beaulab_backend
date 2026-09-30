<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalChatBot\Dto\Hospital\HospitalChatBotConsultationForHospitalDto;
use App\Domains\HospitalChatBot\Queries\Hospital\HospitalChatBotConsultationForHospitalQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;

final class HospitalChatBotConsultationCancelForHospitalAction
{
    public function __construct(
        private readonly HospitalChatBotConsultationForHospitalQuery $query,
        private readonly HospitalChatBotAvailabilityForHospitalAction $availability,
    ) {}

    /** @return array<string, mixed> */
    public function execute(int $consultationId): array
    {
        $account = HospitalChatBotConsultationActiveForHospitalAction::account();

        $consultation = $this->query->findForHospital($consultationId, (int) $account->hospital_id);

        $canceled = $this->query->cancel($consultation, (int) $account->id);

        if (! $canceled) {
            $current = $consultation->fresh(['assignedStaff:id,name']) ?? $consultation;

            // 취소 직전에 상담원이 연결했다면 취소가 아니라 현재 상태를 보여줘야 한다(PRD EX-004).
            if ((string) $current->status === HospitalChatBotConsultationStatus::CONNECTED) {
                throw new CustomException(
                    ErrorCode::CONSULTATION_ALREADY_CLAIMED,
                    '상담원이 이미 연결되어 취소할 수 없습니다.',
                    ['consultation' => HospitalChatBotConsultationForHospitalDto::fromModel($current)],
                );
            }

            // 이미 종료·취소된 상담이면 현재 상태를 그대로 돌려준다. 재시도해도 결과가 같다.
            return [
                'availability' => $this->availability->execute(),
                'consultation' => HospitalChatBotConsultationForHospitalDto::fromModel($current),
            ];
        }

        return [
            'availability' => $this->availability->execute(),
            'consultation' => HospitalChatBotConsultationForHospitalDto::fromModel(
                $consultation->fresh(['assignedStaff:id,name']) ?? $consultation,
            ),
        ];
    }
}
