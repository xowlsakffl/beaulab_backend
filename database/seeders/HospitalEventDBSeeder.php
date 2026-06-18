<?php

namespace Database\Seeders;

use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use Illuminate\Database\Seeder;

final class HospitalEventDBSeeder extends Seeder
{
    public function run(): void
    {
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
            $this->command?->warn('HospitalEventDBSeeder skipped: hospital events are missing.');

            return;
        }

        foreach ($eventIds as $index => $eventId) {
            $eventDBs = HospitalEventDB::factory()
                ->count(($index % 5) + 1)
                ->forEvent($eventId)
                ->create();

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
