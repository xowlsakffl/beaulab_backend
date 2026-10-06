<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \LogicException('데모 데이터는 local/testing 환경에서만 생성할 수 있습니다.');
        }

        DevelopmentDataGuard::assertEnabled();
        $lock = 'demo-seed:'.substr(hash('sha256', DB::getDatabaseName()), 0, 40);
        if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock])->acquired !== 1) {
            throw new \LogicException('다른 데모 시드 작업이 실행 중입니다.');
        }
        try {
            DB::transaction(function (): void {
                DevelopmentDataGuard::assertDemoEmpty();
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
            });
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?)', [$lock]);
        }
    }
}
