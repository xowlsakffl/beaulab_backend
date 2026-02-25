<?php

namespace Database\Seeders;

use App\Common\Authorization\AccessPermissions;
use App\Common\Authorization\AccessRoles;
use App\Domains\Staff\Models\AccountStaff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

final class AccountStaffSeeder extends Seeder
{
    private const SUPER_ADMIN_ROLE = AccessRoles::BEAULAB_SUPER_ADMIN;

    /**
     * @var list<string>
     */
    private const SUPER_ADMIN_DIRECT_PERMISSIONS = [
        AccessPermissions::COMMON_ACCESS,
        AccessPermissions::COMMON_DASHBOARD_SHOW,
        AccessPermissions::COMMON_PROFILE_SHOW,
        AccessPermissions::COMMON_PROFILE_UPDATE,
        AccessPermissions::BEAULAB_HOSPITAL_SHOW,
        AccessPermissions::BEAULAB_HOSPITAL_CREATE,
        AccessPermissions::BEAULAB_HOSPITAL_UPDATE,
        AccessPermissions::BEAULAB_HOSPITAL_DELETE,
        AccessPermissions::BEAULAB_BEAUTY_SHOW,
        AccessPermissions::BEAULAB_BEAUTY_CREATE,
        AccessPermissions::BEAULAB_BEAUTY_UPDATE,
        AccessPermissions::BEAULAB_BEAUTY_DELETE,
        AccessPermissions::BEAULAB_AGENCY_SHOW,
        AccessPermissions::BEAULAB_AGENCY_CREATE,
        AccessPermissions::BEAULAB_AGENCY_UPDATE,
        AccessPermissions::BEAULAB_AGENCY_DELETE,
        AccessPermissions::BEAULAB_USER_SHOW,
        AccessPermissions::BEAULAB_USER_UPDATE,
        AccessPermissions::BEAULAB_USER_DELETE,
        AccessPermissions::BEAULAB_DOCTOR_SHOW,
        AccessPermissions::BEAULAB_DOCTOR_CREATE,
        AccessPermissions::BEAULAB_DOCTOR_UPDATE,
        AccessPermissions::BEAULAB_DOCTOR_DELETE,
        AccessPermissions::BEAULAB_EXPERT_SHOW,
        AccessPermissions::BEAULAB_EXPERT_CREATE,
        AccessPermissions::BEAULAB_EXPERT_UPDATE,
        AccessPermissions::BEAULAB_EXPERT_DELETE,
    ];

    public function run(): void
    {
        $payload = $this->seedPayload();

        if ($payload['email'] === '' || $payload['password'] === '') {
            $this->command?->warn('AccountStaffSeeder skipped: SEED_STAFF_EMAIL / SEED_STAFF_PASSWORD 가 설정되지 않았습니다.');

            return;
        }

        DB::transaction(function () use ($payload): void {
            $staff = AccountStaff::query()->firstOrCreate(
                ['email' => $payload['email']],
                AccountStaff::factory()
                    ->withPassword($payload['password'])
                    ->make($this->staffAttributes($payload))
                    ->toArray()
            );

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $staff->syncRoles([self::SUPER_ADMIN_ROLE]);
            $staff->syncPermissions(self::SUPER_ADMIN_DIRECT_PERMISSIONS);
        });
    }

    /**
     * @return array{email:string,name:string,nickname:string,password:string}
     */
    private function seedPayload(): array
    {
        return [
            'email' => (string) env('SEED_STAFF_EMAIL', ''),
            'name' => (string) env('SEED_STAFF_NAME', ''),
            'nickname' => (string) env('SEED_STAFF_NICKNAME', ''),
            'password' => (string) env('SEED_STAFF_PASSWORD', ''),
        ];
    }

    /**
     * @param array{email:string,name:string,nickname:string,password:string} $payload
     * @return array<string, mixed>
     */
    private function staffAttributes(array $payload): array
    {
        return [
            'email' => $payload['email'],
            'name' => $payload['name'],
            'nickname' => $payload['nickname'],
            'department' => '',
            'job_title' => '',
            'status' => AccountStaff::STATUS_ACTIVE,
        ];
    }
}
