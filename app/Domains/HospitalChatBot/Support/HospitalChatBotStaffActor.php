<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Support;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;

final class HospitalChatBotStaffActor
{
    public static function current(): AccountStaff
    {
        $account = auth()->user();

        if (! $account instanceof AccountStaff) {
            throw new CustomException(ErrorCode::UNAUTHORIZED, '상담원 정보를 확인할 수 없습니다.');
        }

        return $account;
    }
}
