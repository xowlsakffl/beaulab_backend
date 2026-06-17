<?php

namespace Database\Seeders;

use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventConsultation;
use Illuminate\Database\Seeder;

final class HospitalEventConsultationSeeder extends Seeder
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
            $this->command?->warn('HospitalEventConsultationSeeder skipped: hospital events are missing.');

            return;
        }

        foreach ($eventIds as $index => $eventId) {
            $consultations = HospitalEventConsultation::factory()
                ->count(($index % 5) + 1)
                ->forEvent($eventId)
                ->create();

            if ($consultations->count() < 2) {
                continue;
            }

            /** @var HospitalEventConsultation $original */
            $original = $consultations->first();
            /** @var HospitalEventConsultation $duplicate */
            $duplicate = $consultations->last();

            $duplicate->update([
                'name' => $original->name,
                'phone' => $original->phone,
                'status' => HospitalEventConsultation::STATUS_DUPLICATE,
                'contacted_at' => now(),
                'confirmed_at' => null,
                'duplicated_at' => now(),
            ]);
        }
    }
}
