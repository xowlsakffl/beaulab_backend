<?php

namespace App\Domains\AccountBeauty\Actions\Auth;

use App\Domains\AccountBeauty\Dto\Auth\ProfileForAccountBeautyDto;
use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\AccountBeauty\Queries\Auth\ProfileForAccountBeautyQuery;

/**
 * 뷰티 계정 내 프로필 조회 유스케이스.
 * 프로필과 현재 role/permission 스냅샷을 함께 반환한다.
 */
final class GetMyProfileForAccountBeautyAction
{
    public function __construct(
        private readonly ProfileForAccountBeautyQuery $query,
    ) {}

    /**
     * @return array{profile: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(AccountBeauty $beauty): array
    {
        $authorization = $this->query->authorizationSnapshot($beauty);

        return [
            'profile' => ProfileForAccountBeautyDto::fromModel($beauty)->toArray(),
            'roles' => $authorization['roles'],
            'permissions' => $authorization['permissions'],
        ];
    }
}
