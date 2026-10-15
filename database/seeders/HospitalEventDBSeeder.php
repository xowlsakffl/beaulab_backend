<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use Illuminate\Database\Seeder;

final class HospitalEventDBSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        if (HospitalEventDB::withTrashed()->exists()) {
            throw new \LogicException('이벤트 DB 시나리오는 빈 신청 테이블에서만 생성합니다.');
        }
        $users = AccountUser::where('status', 'ACTIVE')->where('created_at', '<', now()->subDays(120))->get();
        $events = HospitalEvent::where('allow_status', 'APPROVED')->get()->filter(fn ($event) => $event->isApplicationOpen())->values();
        if ($users->isEmpty() || $events->isEmpty()) {
            throw new \LogicException('기존 활성 회원과 신청 가능한 이벤트가 필요합니다.');
        }
        for ($day = 0; $day < 60; $day++) {
            for ($offset = 0; $offset < 1 + $day % 4; $offset++) {
                $user = $users[($day + $offset) % $users->count()];
                $at = now()->subDays($day)->subHours(2)->subMinutes($offset);
                $confirmed = ($day + $offset) % 3 !== 0;
                $original = HospitalEventDB::factory()->forEvent($events[($day + $offset) % $events->count()])->create([
                    'account_user_id' => $user->id, 'name' => $user->name, 'phone' => $user->phone,
                    'status' => $confirmed ? 'CONFIRMED' : 'NEW',
                    'allow_status' => HospitalEventDB::allowStatuses()[($day + $offset) % 3],
                    'contacted_at' => $confirmed ? $at->copy()->addMinute() : null,
                    'confirmed_at' => $confirmed ? $at->copy()->addMinute() : null,
                    'created_at' => $at, 'updated_at' => $at->copy()->addMinute(),
                    'privacy_agreed_at' => $at, 'marketing_agreed_at' => $day % 2 ? null : $at,
                ]);
                if ($offset === 0 && $day % 10 === 0) {
                    HospitalEventDB::factory()->forEvent($original->hospital_event_id)->create([
                        'account_user_id' => $user->id, 'name' => $user->name, 'phone' => $user->phone,
                        'status' => 'DUPLICATE', 'allow_status' => $original->allow_status,
                        'contacted_at' => $at->copy()->addMinutes(2), 'confirmed_at' => null,
                        'duplicated_at' => $at->copy()->addMinutes(2), 'privacy_agreed_at' => $at->copy()->addMinute(),
                        'marketing_agreed_at' => null,
                        'created_at' => $at->copy()->addMinute(), 'updated_at' => $at->copy()->addMinutes(2),
                    ]);
                }
            }
        }
    }
}
