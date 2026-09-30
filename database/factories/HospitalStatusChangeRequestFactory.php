<?php

namespace Database\Factories;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalStatusChangeRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HospitalStatusChangeRequest> */
final class HospitalStatusChangeRequestFactory extends Factory
{
    protected $model = HospitalStatusChangeRequest::class;

    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'previous_status' => Hospital::STATUS_ACTIVE,
            'target_status' => Hospital::STATUS_SUSPENDED,
            'status' => HospitalStatusChangeRequest::STATUS_PENDING,
            'reason' => fake()->randomElement(['내부 정비를 위해 상태 변경을 요청합니다.', '운영 일정 변경으로 상태 변경을 요청합니다.']),
            'requested_by_staff_id' => AccountStaff::factory(),
        ];
    }
}
