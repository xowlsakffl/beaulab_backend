<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class HospitalDeleteForStaffAction
{
    public function __construct(
        private readonly HospitalDeleteForStaffQuery $query,
    ) {}

    public function execute(Hospital $hospital): void
    {
        Log::info('병원 삭제(soft delete) 실행', [
            'hospital_id' => $hospital->id,
        ]);

        DB::transaction(function () use ($hospital) {
            $this->query->softDelete($hospital);
        });
    }
}
