<?php

namespace Database\Seeders;

use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Database\Seeder;

final class HospitalVideoSeeder extends Seeder
{
    public function run(): void
    {
        if (Hospital::query()->doesntExist()) {
            $this->call(HospitalSeeder::class);
        }

        $hospitals = Hospital::query()
            ->where('status', Hospital::STATUS_ACTIVE)
            ->where('allow_status', Hospital::ALLOW_APPROVED)
            ->get();

        if ($hospitals->isEmpty()) {
            $this->command?->warn('HospitalVideoSeeder skipped: approved active hospitals are missing.');

            return;
        }

        foreach ($hospitals as $hospitalIndex => $hospital) {
            $videoCount = ($hospitalIndex % 3) + 1;

            for ($index = 0; $index < $videoCount; $index++) {
                $factory = HospitalVideo::factory()
                    ->forHospital($hospital)
                    ->withSeedCategories(random_int(1, 3))
                    ->withSeedMedia(includeVideoFile: ($hospitalIndex + $index) % 4 !== 0);

                if (($hospitalIndex + $index) % 2 === 0) {
                    $factory = $factory->active()->approved();
                }

                $factory->create();
            }
        }
    }
}
