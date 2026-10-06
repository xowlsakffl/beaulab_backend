<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Models\AccountUserAccessLog;
use Illuminate\Database\Seeder;

final class AccountUserSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        if (AccountUser::whereIn('nickname', ['seed_chat_user_1', 'seed_chat_user_2'])->orWhereIn('email', ['seed-chat-user-1@beaulab.test', 'seed-chat-user-2@beaulab.test'])->exists()) {
            throw new \LogicException('고정 샘플 회원이 이미 있습니다. 회원 시드를 재실행하지 않습니다.');
        }
        $createdIds = [];
        AccountUser::factory()->afterCreating(function (AccountUser $user) use (&$createdIds): void {
            $createdIds[] = $user->id;
        })
            ->withIdentity('일반 사용자 1', 'seed_chat_user_1', 'seed-chat-user-1@beaulab.test')
            ->create();

        AccountUser::factory()->afterCreating(function (AccountUser $user) use (&$createdIds): void {
            $createdIds[] = $user->id;
        })
            ->withIdentity('일반 사용자 2', 'seed_chat_user_2', 'seed-chat-user-2@beaulab.test')
            ->create();

        AccountUser::factory()->afterCreating(function (AccountUser $user) use (&$createdIds): void {
            $createdIds[] = $user->id;
        })
            ->count(15)
            ->create();

        AccountUser::factory()->afterCreating(function (AccountUser $user) use (&$createdIds): void {
            $createdIds[] = $user->id;
        })
            ->count(5)
            ->suspended()
            ->create();

        AccountUser::factory()->afterCreating(function (AccountUser $user) use (&$createdIds): void {
            $createdIds[] = $user->id;
        })
            ->count(2)
            ->blocked()
            ->create();

        AccountUser::query()->whereKey($createdIds)->each(function (AccountUser $user): void {
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
