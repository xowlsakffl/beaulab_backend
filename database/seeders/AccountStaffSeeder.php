<?php

namespace Database\Seeders;

use App\Common\Authorization\AccessRoles;
use App\Common\Support\DevelopmentDataGuard;
use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class AccountStaffSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) config('seeding.staff.email'));
        $password = (string) config('seeding.staff.password');
        if ($email === '' || $password === '') {
            $this->command?->warn('직원 계정 시드를 건너뜁니다: SEED_STAFF_EMAIL / SEED_STAFF_PASSWORD를 설정해 주세요.');

            return;
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
            throw new \LogicException('초기 직원 이메일과 12자 이상의 비밀번호가 필요합니다.');
        }
        $withTestAccounts = app()->environment(['local', 'testing']) && config('seeding.development.enabled');
        if ($withTestAccounts) {
            DevelopmentDataGuard::assertEnabled();
        }
        DB::transaction(function () use ($email, $password, $withTestAccounts): void {
            $this->createIfMissing([
                'email' => $email,
                'nickname' => trim((string) config('seeding.staff.nickname', 'admin')),
                'name' => trim((string) config('seeding.staff.name', '관리자')),
                'department' => '', 'job_title' => '',
            ], $password, AccessRoles::BEAULAB_SUPER_ADMIN);

            if (! $withTestAccounts) {
                return;
            }
            $definitions = [
                AccessRoles::BEAULAB_SUPER_ADMIN => ['test_super_admin', '테스트 최고관리자', '개발팀'],
                AccessRoles::BEAULAB_ADMIN => ['test_admin', '테스트 관리자', '운영팀'],
                AccessRoles::BEAULAB_STAFF => ['test_staff', '테스트 직원', '운영팀'],
                AccessRoles::BEAULAB_DEV => ['test_dev', '테스트 개발자', '개발팀'],
            ];
            foreach ($definitions as $role => [$nickname, $name, $department]) {
                $this->createIfMissing([
                    'nickname' => $nickname, 'email' => $nickname.'@beaulab.test',
                    'name' => $name, 'department' => $department, 'job_title' => $name,
                ], $password, $role);
            }
        });
    }

    private function createIfMissing(array $identity, string $password, string $role): void
    {
        // 기존 계정의 비밀번호·상태·권한은 시더 재실행으로 복구하지 않는다.
        if (AccountStaff::withTrashed()->where('email', $identity['email'])->orWhere('nickname', $identity['nickname'])->exists()) {
            $this->command?->warn('기존 직원 계정은 변경하지 않았습니다: '.$identity['nickname']);

            return;
        }
        $staff = AccountStaff::create([
            ...$identity, 'password' => Hash::make($password),
            'status' => AccountStaff::STATUS_ACTIVE, 'email_verified_at' => now(),
            'created_at' => config('seeding.development.enabled') ? now()->subMonths(15) : now(),
        ]);
        $staff->syncRoles([$role]);
        $staff->syncPermissions(\Spatie\Permission\Models\Role::findByName($role, 'staff')->permissions);
        $staff->forceFill(['permissions_initialized_at' => now()])->saveQuietly();
    }
}
