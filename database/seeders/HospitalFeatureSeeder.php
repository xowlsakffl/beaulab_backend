<?php

namespace Database\Seeders;

use App\Domains\HospitalFeature\Definitions\HospitalFeatureDefinitions;
use App\Domains\HospitalFeature\Models\HospitalFeature;
use Illuminate\Database\Seeder;

final class HospitalFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $features = array_map(
            static fn (array $feature): array => [
                ...$feature,
                'status' => HospitalFeature::STATUS_ACTIVE,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            HospitalFeatureDefinitions::all(),
        );

        foreach ($features as $feature) {
            HospitalFeature::firstOrCreate(['code' => $feature['code']], $feature);
        }
    }
}
