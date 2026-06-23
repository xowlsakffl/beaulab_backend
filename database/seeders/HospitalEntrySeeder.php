<?php

namespace Database\Seeders;

use App\Domains\HospitalEntry\Models\HospitalEntry;
use Illuminate\Database\Seeder;

final class HospitalEntrySeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            HospitalEntry::ALLOW_PENDING,
            HospitalEntry::ALLOW_PENDING,
            HospitalEntry::ALLOW_PENDING,
            HospitalEntry::ALLOW_PENDING,
            HospitalEntry::ALLOW_REJECTED,
            HospitalEntry::ALLOW_REJECTED,
            HospitalEntry::ALLOW_REJECTED,
            HospitalEntry::ALLOW_APPROVED,
            HospitalEntry::ALLOW_APPROVED,
            HospitalEntry::ALLOW_APPROVED,
        ];

        foreach ($statuses as $status) {
            HospitalEntry::factory()
                ->withSeedMedia()
                ->create([
                    'allow_status' => $status,
                ]);
        }
    }
}
