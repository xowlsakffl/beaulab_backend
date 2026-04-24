<?php

namespace App\Domains\AccountUser\Queries\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Models\AccountUserBlock;

/**
 * 앱 사용자 차단 생성 쿼리.
 * 차단 대상 확인과 차단 관계 upsert를 담당한다.
 */
final class AccountUserBlockCreateForUserQuery
{
    public function create(AccountUser $blocker, int $blockedUserId): AccountUserBlock
    {
        if ((int) $blocker->id === $blockedUserId) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '본인은 차단할 수 없습니다.');
        }

        $blocked = AccountUser::query()->find($blockedUserId);

        if (! $blocked instanceof AccountUser) {
            throw new CustomException(ErrorCode::USER_NOT_FOUND);
        }

        $now = now();

        AccountUserBlock::query()->upsert(
            [[
                'blocker_user_id' => $blocker->id,
                'blocked_user_id' => $blocked->id,
                'blocked_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['blocker_user_id', 'blocked_user_id'],
            ['updated_at']
        );

        return AccountUserBlock::query()
            ->where('blocker_user_id', $blocker->id)
            ->where('blocked_user_id', $blocked->id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
