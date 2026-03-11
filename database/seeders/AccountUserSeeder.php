<?php

namespace Database\Seeders;

use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Database\Seeder;

final class AccountUserSeeder extends Seeder
{
    public function run(): void
    {
        AccountUser::factory()
            ->count(15)
            ->create();

        AccountUser::factory()
            ->count(5)
            ->suspended()
            ->create();

        AccountUser::factory()
            ->count(2)
            ->blocked()
            ->create();
    }
}
