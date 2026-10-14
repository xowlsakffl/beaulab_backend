<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Support;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;

final class HospitalChatBotStaffActor
{
    public static function allows(mixed $account, string ...$permissions): bool
    {
        if (! $account instanceof AccountStaff || $account->trashed() || ! $account->isActive()) {
            return false;
        }
        foreach ($permissions as $permission) {
            if (! $account->can($permission)) {
                return false;
            }
        }

        return true;
    }

    public static function authorize(string ...$permissions): AccountStaff
    {
        $account = self::current();
        if (! self::allows($account, ...$permissions)) {
            throw new CustomException(ErrorCode::FORBIDDEN, '이 작업을 수행할 권한이 없습니다.');
        }

        return $account;
    }

    public static function current(): AccountStaff
    {
        $account = auth()->user();

        if (! $account instanceof AccountStaff) {
            throw new CustomException(ErrorCode::UNAUTHORIZED, '상담원 정보를 확인할 수 없습니다.');
        }

        return $account;
    }
}
