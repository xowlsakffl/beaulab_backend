<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminAuthorizationSeeder::class, // 권한 정의
            AdminSeeder::class, // 최고관리자
            HospitalSeeder::class, // 병원 테스트
        ]);
    }
}
