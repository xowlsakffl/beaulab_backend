<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Support;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Hospital\Models\Hospital;

final class HospitalReviewRequirements
{
    public static function assertReceptionPhone(Hospital $hospital, string $status): void
    {
        if (in_array($status, [Hospital::ALLOW_PENDING, Hospital::ALLOW_REVIEWING, Hospital::ALLOW_APPROVED], true)
            && ! preg_match('/^010-?\d{4}-?\d{4}$/D', (string) $hospital->ad_reception_phone_1)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '검수 신청 전에 담당자 광고 수신번호를 등록해 주세요.');
        }
    }
}
