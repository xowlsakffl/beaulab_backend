<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalChatBot\Dto\Hospital\HospitalChatBotConsultationForHospitalDto;
use App\Domains\HospitalChatBot\Queries\Hospital\HospitalChatBotConsultationForHospitalQuery;

final class HospitalChatBotConsultationCreateForHospitalAction
{
    public function __construct(
        private readonly HospitalChatBotConsultationForHospitalQuery $query,
        private readonly HospitalChatBotAvailabilityForHospitalAction $availability,
    ) {}

    /**
     * @param  array{source_application?:?string, source_page_path?:?string}  $payload
     * @return array<string, mixed>
     */
    public function execute(array $payload): array
    {
        $account = HospitalChatBotConsultationActiveForHospitalAction::account();
        $hospitalId = (int) $account->hospital_id;

        // 이미 대기·연결 중이면 운영시간과 무관하게 그 상담을 그대로 돌려준다.
        // 운영시간이 끝난 뒤에도 진행 중인 상담은 이어갈 수 있어야 한다.
        $existing = $this->query->active($hospitalId);
        if ($existing !== null) {
            return $this->payload($existing);
        }

        $availability = $this->availability->execute();
        if ($availability['is_available'] !== true) {
            throw new CustomException(
                ErrorCode::CONSULTATION_UNAVAILABLE,
                is_string($availability['message']) ? $availability['message'] : '상담원 연결 시간이 아닙니다.',
                ['availability' => $availability],
            );
        }

        $consultation = $this->query->open(
            account: $account,
            sourceApplication: $payload['source_application'] ?? null,
            sourcePagePath: $payload['source_page_path'] ?? null,
        );

        return $this->payload($consultation);
    }

    /** @return array<string, mixed> */
    private function payload(\App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation $consultation): array
    {
        return [
            'availability' => $this->availability->execute(),
            'consultation' => HospitalChatBotConsultationForHospitalDto::fromModel($consultation),
        ];
    }
}
