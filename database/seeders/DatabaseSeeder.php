<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AuthorizationSeeder::class, // 권한 정의
            AccountStaffSeeder::class, // 뷰랩 내부 직원
            HospitalSeeder::class, // 병원 테스트
            BeautySeeder::class, // 뷰티 테스트
            AccountUserSeeder::class, // 일반회원 테스트
        ]);
    }
}
