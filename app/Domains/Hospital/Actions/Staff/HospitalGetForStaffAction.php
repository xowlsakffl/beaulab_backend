<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Hospital\Dto\Staff\HospitalForStaffDetailDto;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Support\Facades\Gate;

final class HospitalGetForStaffAction
{
    /**
     * @return array{hospital: array}
     */
    public function execute(Hospital $hospital): array
    {
        Gate::authorize('view', $hospital);

        $hospital->load('businessRegistration.certificateMedia');

        return [
            'hospital' => HospitalForStaffDetailDto::fromModel($hospital)->toArray(),
        ];
    }
}
