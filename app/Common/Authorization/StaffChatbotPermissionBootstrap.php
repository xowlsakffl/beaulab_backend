<?php

namespace App\Common\Authorization;

use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class StaffChatbotPermissionBootstrap
{
    public function apply(): array
    {
        $result = DB::transaction(function (): array {
            $roles = Role::where('guard_name', AccessPermissions::GUARD_STAFF)
                ->whereIn('name', AccessRoles::roleNamesByGuard()[AccessPermissions::GUARD_STAFF])
                ->orderBy('id')->lockForUpdate()->get();
            $staffs = AccountStaff::orderBy('id')->lockForUpdate()->get();
            $modelType = (new AccountStaff)->getMorphClass();
            $result = [];
            $changed = false;

            foreach (AccessPermissions::hospitalChatbot() as $name) {
                // 통계는 상담 조회 보유자만 최초 복사한다. 전체 직원 부여 대상이 아니다.
                if ($name === AccessPermissions::BEAULAB_CHATBOT_STATISTICS_SHOW) {
                    continue;
                }
                // 최초 도입 때만 기존 접근을 보존한다. 재실행으로 개별 해제를 되돌리지 않는다.
                if (Permission::where('guard_name', AccessPermissions::GUARD_STAFF)->where('name', $name)->exists()) {
                    $result[$name] = ['created' => false];

                    continue;
                }
                $permission = Permission::create(['name' => $name, 'guard_name' => AccessPermissions::GUARD_STAFF]);
                foreach ($staffs->chunk(500) as $chunk) {
                    DB::table(config('permission.table_names.model_has_permissions'))->insert($chunk->map(fn ($staff) => [
                        'permission_id' => $permission->id, 'model_type' => $modelType, 'model_id' => $staff->id,
                    ])->all());
                }
                if ($roles->isNotEmpty()) {
                    DB::table(config('permission.table_names.role_has_permissions'))->insert($roles->map(fn ($role) => [
                        'permission_id' => $permission->id, 'role_id' => $role->id,
                    ])->all());
                }
                $changed = true;
                $result[$name] = ['created' => true, 'staff_grants' => $staffs->count(), 'role_grants' => $roles->count()];
            }
            if ($changed) {
                // 열려 있던 직원 수정폼이 새 권한을 덮어쓰지 못하게 한다.
                AccountStaff::whereIn('id', $staffs->modelKeys())->increment('lock_version');
            }

            return $result;
        });
        DB::afterCommit(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions());

        return $result;
    }
}
