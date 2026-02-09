<?php

namespace Database\Seeders;

use App\Common\Authorization\AccessPermissions;
use App\Common\Authorization\AccessRoles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class AuthorizationSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /**
         * 1) Permissions: guard별로 생성
         */
        foreach (AccessPermissions::byGuard() as $guard => $permissionNames) {
            foreach ($permissionNames as $permissionName) {
                Permission::findOrCreate($permissionName, $guard);
            }
        }

        /**
         * 2) Roles: guard별로 생성
         */
        foreach (AccessRoles::roleNamesByGuard() as $guard => $roleNames) {
            foreach ($roleNames as $roleName) {
                Role::findOrCreate($roleName, $guard);
            }
        }

        /**
         * 3) Role -> Permission sync: guard별로 매핑
         */
        foreach (AccessRoles::mapByGuard() as $guard => $roleMap) {
            foreach ($roleMap as $roleName => $rolePermissionNames) {
                // role이 없으면 생성
                $role = Role::findOrCreate($roleName, $guard);

                // 같은 guard의 permission만 연결 (Spatie가 내부적으로도 guard 체크함)
                $role->syncPermissions($rolePermissionNames);
            }
        }

        // 캐시 재정리
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
