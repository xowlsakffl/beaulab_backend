<?php

namespace Database\Seeders;

use App\Domains\AccountUser\Models\AccountUserAccessLog;
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

        AccountUser::query()->each(function (AccountUser $user): void {
            if ($user->last_accessed_at === null) {
                return;
            }

            AccountUserAccessLog::query()->create([
                'account_user_id' => $user->id,
                'ip' => $user->last_access_ip,
                'user_agent' => 'Seeder',
                'platform' => fake()->randomElement(['app', 'web']),
                'accessed_at' => $user->last_accessed_at,
            ]);
        });
    }
}
