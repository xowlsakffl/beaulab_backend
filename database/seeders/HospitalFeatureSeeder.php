<?php

namespace Database\Seeders;

use App\Domains\HospitalFeature\Models\HospitalFeature;
use Illuminate\Database\Seeder;

final class HospitalFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        HospitalFeature::query()->upsert([
            ['code' => 'CCTV', 'name' => 'CCTV', 'sort_order' => 1, 'status' => HospitalFeature::STATUS_ACTIVE, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'NIGHT_COUNSELING', 'name' => '야간상담', 'sort_order' => 2, 'status' => HospitalFeature::STATUS_ACTIVE, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ANESTHESIOLOGIST', 'name' => '마취과전문의', 'sort_order' => 3, 'status' => HospitalFeature::STATUS_ACTIVE, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'MULTI_DISCIPLINARY_CARE', 'name' => '분야별협진', 'sort_order' => 4, 'status' => HospitalFeature::STATUS_ACTIVE, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'AFTERCARE', 'name' => '사후관리', 'sort_order' => 5, 'status' => HospitalFeature::STATUS_ACTIVE, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'REAL_NAME_SYSTEM', 'name' => '안심실명제', 'sort_order' => 6, 'status' => HospitalFeature::STATUS_ACTIVE, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'FEMALE_DOCTOR_CARE', 'name' => '여의사진료', 'sort_order' => 7, 'status' => HospitalFeature::STATUS_ACTIVE, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'EMERGENCY_SYSTEM', 'name' => '응급시스템', 'sort_order' => 8, 'status' => HospitalFeature::STATUS_ACTIVE, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'INPATIENT_ROOM', 'name' => '입원실', 'sort_order' => 9, 'status' => HospitalFeature::STATUS_ACTIVE, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'DEDICATED_RECOVERY_ROOM', 'name' => '전담회복실', 'sort_order' => 10, 'status' => HospitalFeature::STATUS_ACTIVE, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'SPECIALIST', 'name' => '전문의', 'sort_order' => 11, 'status' => HospitalFeature::STATUS_ACTIVE, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'PATIENT_ACCOMMODATION', 'name' => '환자전용숙소', 'sort_order' => 12, 'status' => HospitalFeature::STATUS_ACTIVE, 'created_at' => $now, 'updated_at' => $now],
        ], ['code'], ['name', 'sort_order', 'status', 'updated_at']);
    }
}
