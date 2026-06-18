<?php

namespace App\Domains\AccountUser\Actions\User\Auth;

use App\Domains\AccountUser\Dto\User\Auth\AccountUserForAccountUserDto;
use App\Domains\AccountUser\Models\AccountUser;

/**
 * 앱 사용자 내 프로필 조회 유스케이스.
 */
final class GetMyProfileForAccountUserAction
{
    /**
     * @return array{profile: array}
     */
    public function execute(AccountUser $user): array
    {
        return [
            'profile' => AccountUserForAccountUserDto::fromModel($user)->toArray(),
        ];
    }
}
