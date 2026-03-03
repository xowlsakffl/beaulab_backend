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
            $this->command?->warn('AccountStaffSeeder skipped: SEED_STAFF_EMAIL / SEED_STAFF_PASSWORD 가 설정되지 않았습니다.');

            return;
        }

        $factory->createSeededSuperAdmin();
    }
}
