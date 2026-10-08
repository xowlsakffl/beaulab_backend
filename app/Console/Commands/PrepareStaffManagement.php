<?php

namespace App\Console\Commands;

use App\Common\Authorization\AccessPermissions;
use App\Common\Authorization\AccessRoles;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountStaff\Support\StaffPermissionSnapshot;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class PrepareStaffManagement extends Command
{
    protected $signature = 'staff:prepare-management {--apply : 기존 직원 권한을 보존하면서 스키마와 개인 권한을 전환}';

    protected $description = '직원 관리용 컬럼과 기존 권한을 비파괴 방식으로 전환합니다';

    public function handle(): int
    {
        if (! $this->option('apply')) {
            $this->warn('DB 백업과 접속 대상을 확인한 뒤 --apply로 실행하세요.');

            return self::FAILURE;
        }
        if (DB::getDriverName() !== 'mysql') {
            $this->error('MySQL 전용 작업입니다.');

            return self::FAILURE;
        }
        if ((int) DB::selectOne("SELECT GET_LOCK('staff-management-upgrade', 0) AS acquired")->acquired !== 1) {
            return self::FAILURE;
        }
        try {
            $newPermissionNames = array_values(array_diff(AccessPermissions::byGuard()['staff'], Permission::where('guard_name', 'staff')->pluck('name')->all()));
            if (! Schema::hasColumn('account_staffs', 'lock_version')) {
                Schema::table('account_staffs', fn (Blueprint $table) => $table->unsignedInteger('lock_version')->default(0));
            }
            foreach (['phone', 'permissions_initialized_at'] as $column) {
                if (! Schema::hasColumn('account_staffs', $column)) {
                    Schema::table('account_staffs', function (Blueprint $table) use ($column) {
                        $column === 'phone' ? $table->string('phone', 20)->nullable() : $table->timestamp('permissions_initialized_at')->nullable();
                    });
                }
            }
            Schema::table('account_staffs', fn (Blueprint $table) => $table->string('email')->nullable()->change());
            DB::transaction(function () use ($newPermissionNames): void {
                foreach (AccessPermissions::byGuard()['staff'] as $name) {
                    Permission::findOrCreate($name, 'staff');
                }
                Role::where('guard_name', 'staff')->orderBy('id')->lockForUpdate()->get();
                AccountStaff::withTrashed()->orderBy('id')->lockForUpdate()->get()->each(fn ($staff) => StaffPermissionSnapshot::initialize($staff));
                // 기존 역할 템플릿에 새 기능만 최초 추가한다. 직원 권한은 이미 복사된 상태다.
                foreach ([AccessPermissions::BEAULAB_STAFF_PERMISSIONS_MANAGE, AccessPermissions::BEAULAB_INTERNAL_TOOL_SHOW] as $name) {
                    if (in_array($name, $newPermissionNames, true)) {
                        Role::findByName(AccessRoles::BEAULAB_SUPER_ADMIN, 'staff')->givePermissionTo($name);
                    }
                }
            });
            $this->info('직원 스키마와 개인 권한 전환이 완료되었습니다.');

            return self::SUCCESS;
        } finally {
            DB::selectOne("SELECT RELEASE_LOCK('staff-management-upgrade')");
        }
    }
}
