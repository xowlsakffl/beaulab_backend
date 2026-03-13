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
            HospitalSeeder::class,
            BeautySeeder::class,
            AccountUserSeeder::class,
            HospitalTalkSeeder::class,
        ]);
    }
}
