<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Hospital\Dto\Staff\HospitalForStaffDto;
use App\Domains\Hospital\Queries\Staff\HospitalCreateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class HospitalCreateForStaffAction
{
    public function __construct(
        private readonly HospitalCreateForStaffQuery $query,
    ) {}

    /**
     * @return array{hospital: array}
     */
    public function execute(array $filters): array
    {
        Log::info('병원 생성', [
            'filters' => $filters,
        ]);

        $hospital = DB::transaction(function () use ($filters) {
            return $this->query->create($filters)->fresh();
        });

        return [
            'hospital' => HospitalForStaffDto::fromModel($hospital)->toArray(),
        ];
    }
}
