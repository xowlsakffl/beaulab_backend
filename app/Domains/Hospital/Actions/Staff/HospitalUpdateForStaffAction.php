<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Hospital\Dto\Staff\HospitalForStaffDto;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class HospitalUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalUpdateForStaffQuery $query,
    ) {}

    /**
     * @return array{hospital: array}
     */
    public function execute(Hospital $hospital, array $payload): array
    {
        Log::info('병원 정보 수정 실행', [
            'hospital_id' => $hospital->id,
        ]);

        $updated = DB::transaction(function () use ($hospital, $payload) {
            return $this->query->update($hospital, $payload);
        });

        return [
            'hospital' => HospitalForStaffDto::fromModel($updated)->toArray(),
        ];
    }
}
