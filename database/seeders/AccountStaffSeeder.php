<?php

namespace Database\Seeders;

use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Database\Seeder;

final class AccountStaffSeeder extends Seeder
{
    public function run(): void
    {
        $factory = AccountStaff::factory();

        if (! $factory->hasSeedCredentials()) {
            $this->command?->warn('직원 계정 시드 생성을 건너뜁니다: SEED_STAFF_EMAIL / SEED_STAFF_PASSWORD가 설정되지 않았습니다.');

            return;
        }

        $factory->createSeededSuperAdmin();

        if (app()->environment('local')) {
            $factory->createSeededRoleTestAccounts();
        }
    }
}
