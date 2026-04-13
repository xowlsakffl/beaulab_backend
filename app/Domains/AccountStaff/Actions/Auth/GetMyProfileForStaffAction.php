<?php


namespace App\Domains\AccountStaff\Actions\Auth;

use App\Domains\AccountStaff\Dto\Auth\ProfileForStaffDto;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountStaff\Queries\Auth\ProfileForStaffQuery;

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
