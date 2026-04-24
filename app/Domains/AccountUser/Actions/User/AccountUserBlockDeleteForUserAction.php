<?php

namespace App\Domains\AccountUser\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\User\AccountUserBlockDeleteForUserQuery;

/**
 * 사용자 차단 해제 유스케이스.
 * 차단 관계 삭제 결과를 API 응답에 맞는 단순한 불리언 값으로 반환한다.
 */
final class AccountUserBlockDeleteForUserAction
{
    public function __construct(
        private readonly AccountUserBlockDeleteForUserQuery $query,
    ) {}

    public function execute(AccountUser $user, int $blockedUserId): array
    {
        return [
            'unblocked' => $this->query->delete($user, $blockedUserId) > 0,
        ];
    }
}
