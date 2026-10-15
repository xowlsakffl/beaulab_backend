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
        if (AccountUser::withTrashed()->exists()) {
            throw new \LogicException('회원 시나리오는 빈 회원 테이블에서만 생성합니다.');
        }
        $channels = AccountUser::signupChannels();
        for ($index = 0; $index < 120; $index++) {
            $created = now()->subDays($index < 30 ? $index : 31 + ($index - 30) * 4)->subHours(2);
            $lastAccess = $index % 8 === 0 ? null : now()->subDays(min($index, $index % 7))->subMinutes($index + 1);
            $user = AccountUser::factory()->withPassword((string) config('seeding.staff.password'))->create([
                'name' => sprintf('테스트회원 %03d', $index + 1),
                'nickname' => $index < 2 ? 'seed_chat_user_'.($index + 1) : sprintf('다정한구름%03d', $index + 1),
                'email' => $index < 2 ? 'seed-chat-user-'.($index + 1).'@beaulab.test' : sprintf('member-%03d@beaulab.test', $index + 1),
                'phone' => sprintf('010-0000-%04d', $index + 1),
                'status' => match (true) {
                    $index < 110 => 'ACTIVE', $index < 116 => 'SUSPENDED', $index < 118 => 'BLOCKED', default => 'WITHDRAWN'
                },
                'signup_channel' => $channels[$index % count($channels)],
                'created_at' => $created, 'updated_at' => $created,
                'email_verified_at' => $created, 'last_login_at' => $lastAccess,
                'last_accessed_at' => $lastAccess, 'last_access_ip' => $lastAccess ? '192.0.2.'.($index + 1) : null,
            ]);
            if ($lastAccess) {
                AccountUserAccessLog::create([
                    'account_user_id' => $user->id, 'ip' => $user->last_access_ip,
                    'user_agent' => 'Development fixture', 'platform' => $index % 2 ? 'app' : 'web',
                    'accessed_at' => $lastAccess,
                ]);
            }
        }
    }
}
