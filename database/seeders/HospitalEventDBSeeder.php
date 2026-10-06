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
            $this->command?->warn('이벤트 DB 시드 생성을 건너뜁니다: 병의원 이벤트가 없습니다.');

            return;
        }

        foreach ($eventIds as $index => $eventId) {
            $eventDBs = HospitalEventDB::factory()
                ->count(($index % 5) + 1)
                ->forEvent($eventId)
                ->create(['account_user_id' => fn () => $userIds->random()]);

            if ($eventDBs->count() < 2) {
                continue;
            }

            /** @var HospitalEventDB $original */
            $original = $eventDBs->first();
            /** @var HospitalEventDB $duplicate */
            $duplicate = $eventDBs->last();

            $duplicate->update([
                'name' => $original->name,
                'phone' => $original->phone,
                'status' => HospitalEventDB::STATUS_DUPLICATE,
                'contacted_at' => now(),
                'confirmed_at' => null,
                'duplicated_at' => now(),
            ]);
        }
    }
}
