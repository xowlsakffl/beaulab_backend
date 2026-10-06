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
        $userIds = AccountUser::where('status', AccountUser::STATUS_ACTIVE)->pluck('id');
        if ($userIds->isEmpty()) {
            throw new \LogicException('활성 샘플 회원을 먼저 생성해 주세요.');
        }
        $eventIds = HospitalEvent::query()
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->values();

        if ($eventIds->isEmpty()) {
            $this->call(HospitalEventSeeder::class);

            $eventIds = HospitalEvent::query()
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values();
        }

        if ($eventIds->isEmpty()) {
            $this->command?->warn('리얼모델 DB 시드 생성을 건너뜁니다: 병의원 이벤트가 없습니다.');

            return;
        }

        foreach ($eventIds as $index => $eventId) {
            $applicationCount = ($index % 4) + 1;

            for ($offset = 0; $offset < $applicationCount; $offset++) {
                HospitalEventRealModelDB::factory()
                    ->forEvent($eventId)
                    ->withSeedMedia(random_int(1, 5))
                    ->create(['account_user_id' => fn () => $userIds->random()]);
            }
        }
    }
}
