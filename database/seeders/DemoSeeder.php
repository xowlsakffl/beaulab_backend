<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \LogicException('데모 데이터는 local/testing 환경에서만 생성할 수 있습니다.');
        }

        $this->call([
            HospitalEntrySeeder::class,
            HospitalSeeder::class,
            HospitalDoctorSeeder::class,
            HospitalVideoSeeder::class,
            BeautySeeder::class,
            BeautyExpertSeeder::class,
            AccountUserSeeder::class,
            TalkSeeder::class,
            HospitalReviewSeeder::class,
            HospitalEvaluationSeeder::class,
            HospitalEventSeeder::class,
            HospitalEventDBSeeder::class,
            HospitalEventRealModelDBSeeder::class,
            HospitalWalletSeeder::class,
        ]);
    }
}
