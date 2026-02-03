<?php

namespace Database\Seeders;

use App\Domains\Admin\Models\Admin;
use App\Domains\Admin\Models\AdminMembership;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

final class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('SEED_ADMIN_EMAIL', '');
        $name = (string) env('SEED_ADMIN_NAME', '');
        $nickname = (string) env('SEED_ADMIN_NICKNAME', '');
        $password = (string) env('SEED_ADMIN_PASSWORD', '');

        if ($email === '' || $password === '') {
            $this->command?->warn(
                'AdminSeeder skipped: SEED_ADMIN_EMAIL / SEED_ADMIN_PASSWORD 가 설정되지 않았습니다.'
            );
            return;
        }

        DB::transaction(function () use ($email, $name, $nickname, $password) {

            /** @var Admin $admin */
            $admin = Admin::query()->where('email', $email)->first()
                ?? Admin::factory()
                    ->withPassword($password)
                    ->create([
                        'email'    => $email,
                        'name'     => $name,
                        'nickname' => $nickname,
                        'status'   => Admin::STATUS_ACTIVE,
                    ]);

            // active_membership_id 기준으로 체크
            if (!$admin->active_membership_id) {

                $membership = AdminMembership::factory()
                    ->beaulabSuperAdmin()
                    ->create([
                        'admin_id' => $admin->id,
                    ]);

                $admin->forceFill([
                    'active_membership_id' => $membership->id,
                ])->save();
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $admin->syncRoles([
                'beaulab.super_admin',
            ]);
        });
    }
}
