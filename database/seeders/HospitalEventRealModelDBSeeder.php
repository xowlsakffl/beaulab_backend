<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use Illuminate\Database\Seeder;

final class HospitalEventRealModelDBSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        if (HospitalEventRealModelDB::withTrashed()->exists()) {
            throw new \LogicException('리얼모델 시나리오는 빈 신청 테이블에서만 생성합니다.');
        }
        $users = AccountUser::where('status', 'ACTIVE')->where('created_at', '<', now()->subDays(120))->get();
        $events = HospitalEvent::whereHas('categories', fn ($q) => $q->where('code', 'HP_REAL_MODEL'))
            ->where('allow_status', 'APPROVED')->get()->filter(fn ($event) => $event->isApplicationOpen())->values();
        if ($users->isEmpty() || $events->isEmpty()) {
            throw new \LogicException('기존 활성 회원과 신청 가능한 리얼모델 이벤트가 필요합니다.');
        }
        for ($index = 0; $index < 90; $index++) {
            $user = $users[$index % $users->count()];
            $factory = HospitalEventRealModelDB::factory()->forEvent($events[$index % $events->count()]);
            if ($index % 5 !== 0) {
                $factory = $factory->withSeedMedia($index === 1 ? 10 : $index % 4 + 1);
            }
            $at = now()->subDays($index % 60)->subHours(3);
            $factory->create([
                'account_user_id' => $user->id, 'name' => $user->name, 'phone' => $user->phone,
                'status' => HospitalEventRealModelDB::statuses()[$index % 3],
                'instagram_url' => null, 'blog_url' => null,
                'created_at' => $at, 'updated_at' => $at->copy()->addMinute(),
            ]);
        }
    }
}
