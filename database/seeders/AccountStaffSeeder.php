<?php

namespace Database\Seeders;

use App\Domains\Staff\Models\AccountStaff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

final class AccountStaffSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('SEED_STAFF_EMAIL', '');
        $name = (string) env('SEED_STAFF_NAME', '');
        $nickname = (string) env('SEED_STAFF_NICKNAME', '');
        $password = (string) env('SEED_STAFF_PASSWORD', '');

        if ($email === '' || $password === '') {
            $this->command?->warn(
                'AccountStaffSeeder skipped: SEED_STAFF_EMAIL / SEED_STAFF_PASSWORD 가 설정되지 않았습니다.'
            );
            return;
        }

        DB::transaction(function () use ($email, $name, $nickname, $password) {

            /** @var AccountStaff $staff */
            $staff = AccountStaff::query()->where('email', $email)->first()
                ?? AccountStaff::factory()
                    ->withPassword($password)
                    ->create([
                        'email'    => $email,
                        'name'     => $name,
                        'nickname' => $nickname,
                        'department'=> '',
                        'job_title' => '',
                        'status'   => AccountStaff::STATUS_ACTIVE,
                    ]);


            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $staff->syncRoles([
                'beaulab.super_admin',
            ]);
        });
    }
}
