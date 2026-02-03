<?php
// database/seeders/AdminAuthorizationSeeder.php

namespace Database\Seeders;

use App\Modules\Admin\Authorization\AdminPermissions;
use App\Modules\Admin\Authorization\AdminRoles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminAuthorizationSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = AdminPermissions::GUARD;

        // 1) Permission 동기화
        $permissionNames = AdminPermissions::all();
        foreach ($permissionNames as $permissionName) {
            Permission::findOrCreate($permissionName, $guard);
        }

        // 2) Role 생성 + Permission 매핑(sync)
        $roleMap = AdminRoles::map();
        foreach ($roleMap as $roleName => $rolePermissionNames) {
            $role = Role::findOrCreate($roleName, $guard);
            $role->syncPermissions($rolePermissionNames);
        }
    }
}
