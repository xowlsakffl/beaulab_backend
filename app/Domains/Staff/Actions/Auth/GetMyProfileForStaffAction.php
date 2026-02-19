<?php


namespace App\Domains\Staff\Actions\Auth;

use App\Domains\Staff\Dto\Auth\ProfileForStaffDto;
use App\Domains\Staff\Models\AccountStaff;

final class GetMyProfileForStaffAction
{
    /**
     * @return array{profile: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(AccountStaff $staff): array
    {
        return [
            'profile' => ProfileForStaffDto::fromModel($staff)->toArray(),
            'roles' => $staff->getRoleNames()->values()->all(),
            'permissions' => $staff->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
