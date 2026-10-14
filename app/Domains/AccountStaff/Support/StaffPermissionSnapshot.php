<?php

namespace App\Domains\AccountStaff\Support;

use App\Common\Authorization\AccessPermissions;
use App\Common\Authorization\AccessRoles;
use App\Domains\AccountStaff\Models\AccountStaff;
use Spatie\Permission\Models\Permission;

final class StaffPermissionSnapshot
{
    // 호출자는 직원 행을 잠근 트랜잭션 안에서 실행한다.
    public static function initialize(AccountStaff $staff): void
    {
        if ($staff->permissions_initialized_at !== null) {
            return;
        }
        $names = StaffPermissionCatalog::activeNames($staff->getAllPermissions()->pluck('name')->all());
        // 새 관리 권한은 기존 총괄에만 최초 부여한다. 이후에는 역할로 우회하지 않는다.
        if ($staff->hasRole(AccessRoles::BEAULAB_SUPER_ADMIN)) {
            $names[] = AccessPermissions::BEAULAB_STAFF_PERMISSIONS_MANAGE;
        }
        if ($staff->hasAnyRole([AccessRoles::BEAULAB_SUPER_ADMIN, AccessRoles::BEAULAB_DEV])) {
            $names[] = AccessPermissions::BEAULAB_INTERNAL_TOOL_SHOW;
        }
        foreach (array_unique($names) as $name) {
            Permission::findOrCreate($name, 'staff');
        }
        $staff->syncPermissions(array_unique($names));
        $staff->forceFill(['permissions_initialized_at' => now()])->saveQuietly();
    }
}
