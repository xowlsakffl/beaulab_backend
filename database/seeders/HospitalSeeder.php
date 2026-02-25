<?php

namespace Database\Seeders;

use App\Domains\Hospital\Models\Hospital;
use Illuminate\Database\Seeder;

final class HospitalSeeder extends Seeder
{
    public function run(): void
    {
        Hospital::factory()
            ->count(40)
            ->approved()
            ->active()
            ->withBusinessRegistration()
            ->withPartner()
            ->create();

        // 나머지 랜덤 병원 + 소유주
        Hospital::factory()
            ->count(10)
            ->withBusinessRegistration()
            ->withPartner()
            ->create();
    }
}
