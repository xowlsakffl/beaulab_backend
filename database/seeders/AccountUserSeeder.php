<?php

namespace Database\Seeders;

use App\Domains\User\Models\AccountUser;
use Illuminate\Database\Seeder;

final class AccountUserSeeder extends Seeder
{
    public function run(): void
    {
        AccountUser::factory()
            ->count(35)
            ->create();

        AccountUser::factory()
            ->count(10)
            ->suspended()
            ->create();

        AccountUser::factory()
            ->count(5)
            ->blocked()
            ->create();
    }
}
