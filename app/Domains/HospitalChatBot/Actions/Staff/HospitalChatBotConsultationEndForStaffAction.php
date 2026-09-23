<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotConsultationForStaffDto;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotConsultationForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use App\Domains\HospitalChatBot\Support\HospitalChatBotStaffActor;

final class HospitalChatBotConsultationEndForStaffAction
{
    public function __construct(private readonly HospitalChatBotConsultationForStaffQuery $query) {}

    /** @return array<string, mixed> */
    public function execute(int $consultationId): array
    {
        $staff = HospitalChatBotStaffActor::current();
        $consultation = $this->query->find($consultationId);

        // 담당 상담원만 종료할 수 있다(PRD 11.4-5).
        if ((string) $consultation->status === HospitalChatBotConsultationStatus::CONNECTED
            && (int) $consultation->assigned_staff_id !== (int) $staff->id
        ) {
            throw new CustomException(ErrorCode::FORBIDDEN, '담당 상담원만 상담을 종료할 수 있습니다.');
        }

        if ($this->query->end($consultationId, (int) $staff->id)) {
            return HospitalChatBotConsultationForStaffDto::fromModel($this->query->find($consultationId));
        }

        $current = $this->query->find($consultationId);

        // 이미 종료된 상담이면 같은 결과를 그대로 돌려준다. 재시도해도 안전하다(PRD 11.3).
        if ((string) $current->status === HospitalChatBotConsultationStatus::ENDED) {
            return HospitalChatBotConsultationForStaffDto::fromModel($current);
        }

        throw new CustomException(
            ErrorCode::CONSULTATION_NOT_ACTIVE,
            '연결 중인 상담만 종료할 수 있습니다.',
            ['consultation' => HospitalChatBotConsultationForStaffDto::fromModel($current)],
        );
    }
}
