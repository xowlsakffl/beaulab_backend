<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Support;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\HospitalAccountInvitation;

final class HospitalAccountInvitationGuard
{
    public static function assertActive(?HospitalAccountInvitation $invitation): HospitalAccountInvitation
    {
        if (
            $invitation === null
            || ! in_array($invitation->source_type, HospitalAccountInvitation::sourceTypes(), true)
            || ! $invitation->isActive()
        ) {
            throw new CustomException(
                ErrorCode::TOKEN_ERROR,
                '계정 생성 링크가 유효하지 않거나 만료되었습니다.',
            );
        }

        return $invitation;
    }
}
