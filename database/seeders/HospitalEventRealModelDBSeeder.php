<?php

namespace Database\Seeders;

use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use Illuminate\Database\Seeder;

final class HospitalEventRealModelDBSeeder extends Seeder
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
            $this->command?->warn('HospitalEventRealModelDBSeeder skipped: hospital events are missing.');

            return;
        }

        foreach ($eventIds as $index => $eventId) {
            $applicationCount = ($index % 4) + 1;

            for ($offset = 0; $offset < $applicationCount; $offset++) {
                HospitalEventRealModelDB::factory()
                    ->forEvent($eventId)
                    ->withSeedMedia(random_int(1, 5))
                    ->create();
            }
        }
    }
}
