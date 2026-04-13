<?php


namespace App\Domains\AccountStaff\Actions\Auth;

use App\Domains\AccountStaff\Dto\Auth\ProfileForStaffDto;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountStaff\Queries\Auth\ProfileForStaffQuery;

/**
 * 스태프 내 프로필 조회 유스케이스.
 * 프로필과 현재 role/permission 스냅샷을 함께 반환한다.
 */
final class GetMyProfileForStaffAction
{
    public function __construct(
        private readonly ProfileForStaffQuery $query,
    ) {}

    /**
     * @return array{profile: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(AccountStaff $staff): array
    {
        $authorization = $this->query->authorizationSnapshot($staff);

        return [
            'profile' => ProfileForStaffDto::fromModel($staff)->toArray(),
            'roles' => $authorization['roles'],
            'permissions' => $authorization['permissions'],
        ];
    }
}
