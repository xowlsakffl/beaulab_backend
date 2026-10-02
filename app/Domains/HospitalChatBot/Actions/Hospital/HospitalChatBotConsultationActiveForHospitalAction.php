<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\HospitalChatBot\Dto\Hospital\HospitalChatBotConsultationForHospitalDto;
use App\Domains\HospitalChatBot\Queries\Hospital\HospitalChatBotConsultationForHospitalQuery;

/**
 * SDK가 열릴 때마다 호출한다. 새로고침·위젯 재열기 후 대기·연결 상태를 복구하는 근거가 된다.
 */
final class HospitalChatBotConsultationActiveForHospitalAction
{
    public function __construct(
        private readonly HospitalChatBotConsultationForHospitalQuery $query,
        private readonly HospitalChatBotAvailabilityForHospitalAction $availability,
    ) {}

    /** @return array<string, mixed> */
    public function execute(): array
    {
        $account = self::account();

        return [
            'availability' => $this->availability->execute(),
            'consultation' => HospitalChatBotConsultationForHospitalDto::fromModel(
                $this->query->active((int) $account->hospital_id),
            ),
        ];
    }

    public static function account(): AccountHospital
    {
        $account = auth()->user();

        if (! $account instanceof AccountHospital || $account->hospital_id === null) {
            throw new CustomException(ErrorCode::UNAUTHORIZED, '병의원 정보를 확인할 수 없습니다.');
        }

        return $account;
    }
}
