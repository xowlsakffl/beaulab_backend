<?php

namespace App\Domains\AccountUser\Queries\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Models\AccountUserBlock;

/**
 * 앱 사용자 차단 해제 쿼리.
 * 차단 관계 삭제를 담당한다.
 */
final class AccountUserBlockDeleteForUserQuery
{
    public function delete(AccountUser $blocker, int $blockedUserId): int
    {
        if ((int) $blocker->id === $blockedUserId) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '본인은 차단 해제 대상이 될 수 없습니다.');
        }

        return AccountUserBlock::query()
            ->where('blocker_user_id', $blocker->id)
            ->where('blocked_user_id', $blockedUserId)
            ->delete();
    }
}
