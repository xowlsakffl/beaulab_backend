<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AuthorizationSeeder::class,
            AccountStaffSeeder::class,
            CategorySeeder::class,
            HospitalFeatureSeeder::class,
            HospitalSeeder::class,
            HospitalDoctorSeeder::class,
            BeautySeeder::class,
            BeautyExpertSeeder::class,
            AccountUserSeeder::class,
            TalkSeeder::class,
        ]);
    }
}
