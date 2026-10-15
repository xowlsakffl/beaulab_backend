<?php

namespace App\Common\Authorization;

use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class StaffPermissionSplit
{
    private const SPLITS = [
        AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW => AccessPermissions::BEAULAB_CHATBOT_STATISTICS_SHOW,
        AccessPermissions::BEAULAB_HOSPITAL_WALLET_SHOW => AccessPermissions::BEAULAB_HOSPITAL_WALLET_DASHBOARD_SHOW,
        AccessPermissions::BEAULAB_REMOTE_CONSULTATION_APPROVE => AccessPermissions::BEAULAB_REMOTE_CONSULTATION_ASSIGN,
        AccessPermissions::BEAULAB_HOSPITAL_EVENT_AD_SHOW => AccessPermissions::BEAULAB_HOSPITAL_EVENT_AD_CALENDAR_SHOW,
        AccessPermissions::BEAULAB_VIDEO_SHOW => AccessPermissions::BEAULAB_VIDEO_CONSENT_SHOW,
        AccessPermissions::BEAULAB_USER_SHOW => AccessPermissions::BEAULAB_USER_STATISTICS_SHOW,
        AccessPermissions::BEAULAB_BANNER_EXPOSURE_SHOW => AccessPermissions::BEAULAB_OTHER_EXPOSURE_SHOW,
        AccessPermissions::BEAULAB_BANNER_EXPOSURE_CREATE => AccessPermissions::BEAULAB_OTHER_EXPOSURE_CREATE,
        AccessPermissions::BEAULAB_BANNER_EXPOSURE_UPDATE => AccessPermissions::BEAULAB_OTHER_EXPOSURE_UPDATE,
        AccessPermissions::BEAULAB_BANNER_EXPOSURE_DELETE => AccessPermissions::BEAULAB_OTHER_EXPOSURE_DELETE,
    ];

    public function apply(): array
    {
        $result = DB::transaction(function (): array {
            // 직원 권한 편집과 같은 순서로 잠가 이전 중 권한 저장을 직렬화한다.
            Role::where('guard_name', AccessPermissions::GUARD_STAFF)->orderBy('id')->lockForUpdate()->get();
            $staffs = AccountStaff::withTrashed()->orderBy('id')->lockForUpdate()->get();
            $modelType = (new AccountStaff)->getMorphClass();
            $result = [];
            foreach (self::SPLITS as $sourceName => $targetName) {
                // 최초 생성 때만 복사한다. 이후 개별 해제한 권한을 복원하지 않는다.
                if (Permission::where('guard_name', AccessPermissions::GUARD_STAFF)->where('name', $targetName)->exists()) {
                    $result[$targetName] = ['created' => false];

                    continue;
                }
                $source = Permission::where('guard_name', AccessPermissions::GUARD_STAFF)->where('name', $sourceName)->first();
                $target = Permission::create(['name' => $targetName, 'guard_name' => AccessPermissions::GUARD_STAFF]);
                $staffCount = 0;
                $roleCount = 0;
                if ($source) {
                    $modelTable = config('permission.table_names.model_has_permissions');
                    $roleTable = config('permission.table_names.role_has_permissions');
                    $sourceGrants = DB::table($modelTable)->where('permission_id', $source->id)->where('model_type', $modelType);
                    $staffIds = (clone $sourceGrants)->pluck('model_id');
                    $sourceRoleIds = DB::table($roleTable)->where('permission_id', $source->id)
                        ->whereIn('role_id', Role::where('guard_name', AccessPermissions::GUARD_STAFF)->select('id'))->pluck('role_id');
                    $inheritedStaffIds = AccountStaff::withTrashed()->whereNull('permissions_initialized_at')
                        ->whereHas('roles', fn ($query) => $query->whereIn('roles.id', $sourceRoleIds))->pluck('id');
                    $staffCount = DB::table($modelTable)->insertUsing(
                        ['permission_id', 'model_type', 'model_id'],
                        (clone $sourceGrants)->selectRaw('? as permission_id, model_type, model_id', [$target->id]),
                    );
                    $roleCount = DB::table($roleTable)->insertUsing(
                        ['permission_id', 'role_id'],
                        DB::table($roleTable)->where('permission_id', $source->id)
                            ->whereIn('role_id', Role::where('guard_name', AccessPermissions::GUARD_STAFF)->select('id'))
                            ->selectRaw('? as permission_id, role_id', [$target->id]),
                    );
                    // 열린 수정폼이 새 권한을 오래된 값으로 덮어쓰지 못하게 한다.
                    AccountStaff::withTrashed()->whereIn('id', $staffs->whereIn('id', $staffIds->merge($inheritedStaffIds))->modelKeys())->increment('lock_version');
                }
                $result[$targetName] = ['created' => true, 'staff_grants' => $staffCount, 'role_grants' => $roleCount];
            }

            return $result;
        });
        DB::afterCommit(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions());

        return $result;
    }
}
