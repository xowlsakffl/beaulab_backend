<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Common\Authorization\AccessPermissions as P;
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
        $staff = HospitalChatBotStaffActor::authorize(P::BEAULAB_CHATBOT_CONSULTATION_SHOW, P::BEAULAB_CHATBOT_CONSULTATION_PROCESS);

        // 존재하지 않는 상담이면 여기서 404가 난다.
        $this->query->find($consultationId);

        // 담당 상담원 조건은 UPDATE의 WHERE에 함께 들어간다(PRD 11.4-5).
        // 먼저 읽은 행으로 판단하면 그사이 다른 상담원이 연결한 상담을 종료할 수 있다.
        if ($this->query->end($consultationId, (int) $staff->id)) {
            return HospitalChatBotConsultationForStaffDto::fromModel($this->query->find($consultationId));
        }

        $current = $this->query->find($consultationId);
        $assignedStaffId = $current->assigned_staff_id !== null ? (int) $current->assigned_staff_id : null;

        // 남의 상담이면 상태와 무관하게 거절한다.
        if ($assignedStaffId !== null && $assignedStaffId !== (int) $staff->id) {
            throw new CustomException(ErrorCode::FORBIDDEN, '담당 상담원만 상담을 종료할 수 있습니다.');
        }

        // 내가 담당한 상담을 다시 종료하면 같은 결과를 그대로 돌려준다(PRD 11.3).
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
