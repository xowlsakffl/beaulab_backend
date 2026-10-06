<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use Illuminate\Database\Seeder;

final class HospitalEntrySeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        $statuses = [
            HospitalEntry::ALLOW_PENDING,
            HospitalEntry::ALLOW_PENDING,
            HospitalEntry::ALLOW_PENDING,
            HospitalEntry::ALLOW_PENDING,
            HospitalEntry::ALLOW_REVIEWING,
            HospitalEntry::ALLOW_REVIEWING,
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
