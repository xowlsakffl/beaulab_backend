<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotConsultationForStaffDto;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotConsultationForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use App\Domains\HospitalChatBot\Support\HospitalChatBotStaffActor;

final class HospitalChatBotConsultationClaimForStaffAction
{
    public function __construct(private readonly HospitalChatBotConsultationForStaffQuery $query) {}

    /** @return array<string, mixed> */
    public function execute(int $consultationId): array
    {
        $staff = HospitalChatBotStaffActor::current();

        // 존재하지 않는 상담이면 여기서 404가 난다.
        $this->query->find($consultationId);

        if ($this->query->claim($consultationId, (int) $staff->id, $staff->name)) {
            return HospitalChatBotConsultationForStaffDto::fromModel($this->query->find($consultationId));
        }

        // 조건부 UPDATE가 0건이면 그사이 누군가 상태를 바꾼 것이다. 현재 상태로 응답을 갈라준다.
        $current = $this->query->find($consultationId);
        $isConnected = (string) $current->status === HospitalChatBotConsultationStatus::CONNECTED;

        // 내가 이미 잡은 상담이면 연타해도 성공으로 취급한다.
        if ($isConnected && (int) $current->assigned_staff_id === (int) $staff->id) {
            return HospitalChatBotConsultationForStaffDto::fromModel($current);
        }

        throw new CustomException(
            $isConnected ? ErrorCode::CONSULTATION_ALREADY_CLAIMED : ErrorCode::CONSULTATION_NOT_ACTIVE,
            $isConnected ? '다른 상담원이 이미 연결한 상담입니다.' : '이미 종료되었거나 취소된 상담입니다.',
            ['consultation' => HospitalChatBotConsultationForStaffDto::fromModel($current)],
        );
    }
}
