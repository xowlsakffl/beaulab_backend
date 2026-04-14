<?php

namespace App\Domains\AccountUser\Actions\User\Auth;

use App\Domains\AccountUser\Dto\User\Auth\ProfileForAccountUserDto;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\User\Auth\ProfileForAccountUserQuery;

/**
 * 앱 사용자 내 프로필 조회 유스케이스.
 * 프로필 DTO와 현재 권한 스냅샷을 함께 반환한다.
 */
final class GetMyProfileForAccountUserAction
{
    public function __construct(
        private readonly ProfileForAccountUserQuery $query,
    ) {}

    /**
     * @return array{profile: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(AccountUser $user): array
    {
        $authorization = $this->query->authorizationSnapshot($user);

        return [
            'profile' => ProfileForAccountUserDto::fromModel($user)->toArray(),
            'roles' => $authorization['roles'],
            'permissions' => $authorization['permissions'],
        ];
    }
}
