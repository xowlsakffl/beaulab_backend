<?php

namespace Database\Seeders;

use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Database\Seeder;

final class AccountUserSeeder extends Seeder
{
    public function run(): void
    {
        AccountUser::factory()
            ->withIdentity('일반 사용자 1', 'seed_chat_user_1', 'seed-chat-user-1@beaulab.test')
            ->create();

        AccountUser::factory()
            ->withIdentity('일반 사용자 2', 'seed_chat_user_2', 'seed-chat-user-2@beaulab.test')
            ->create();

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
