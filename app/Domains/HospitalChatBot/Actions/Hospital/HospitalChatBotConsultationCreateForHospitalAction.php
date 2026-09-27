<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalChatBot\Dto\Hospital\HospitalChatBotConsultationForHospitalDto;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Queries\Hospital\HospitalChatBotConsultationForHospitalQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotLog;

final class HospitalChatBotConsultationCreateForHospitalAction
{
    public function __construct(
        private readonly HospitalChatBotConsultationForHospitalQuery $query,
        private readonly HospitalChatBotAvailabilityForHospitalAction $availability,
    ) {}

    /**
     * @param  array{source_application?:?string, source_page_path?:?string, context_qna_id?:?int}  $payload
     * @return array<string, mixed>
     */
    public function execute(array $payload): array
    {
        $account = HospitalChatBotConsultationActiveForHospitalAction::account();
        $hospitalId = (int) $account->hospital_id;

        // 운영시간은 요청당 한 번만 판정한다. 두 번 읽으면 그사이 마감 시각을 넘겼을 때
        // 상담은 만들어졌는데 응답의 availability는 닫힘으로 나가는 상태가 생긴다.
        $availability = $this->availability->execute();

        // 이미 대기·연결 중이면 운영시간과 무관하게 그 상담을 그대로 돌려준다.
        // 운영시간이 끝난 뒤에도 진행 중인 상담은 이어갈 수 있어야 한다.
        $existing = $this->query->active($hospitalId);
        if ($existing !== null) {
            /*
             * 연타나 여러 탭에서 온 요청이다. open()까지 가지 않고 여기서 돌려주므로
             * 로그도 여기서 남겨야 한다. Query 안에만 두면 이 경로가 통째로 빠진다.
             */
            HospitalChatBotLog::info(HospitalChatBotLog::CONSULTATION_REQUEST_DEDUPED, [
                'consultation_id' => (int) $existing->id,
                'hospital_id' => $hospitalId,
                'status' => (string) $existing->status,
                'via' => 'active_lookup',
            ]);

            return $this->payload($existing, $availability);
        }

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
            contextQnaId: $payload['context_qna_id'] ?? null,
        );

        return $this->payload($consultation, $availability);
    }

    /**
     * @param  array<string, mixed>  $availability
     * @return array<string, mixed>
     */
    private function payload(HospitalChatBotConsultation $consultation, array $availability): array
    {
        return [
            'availability' => $availability,
            'consultation' => HospitalChatBotConsultationForHospitalDto::fromModel($consultation),
        ];
    }
}
