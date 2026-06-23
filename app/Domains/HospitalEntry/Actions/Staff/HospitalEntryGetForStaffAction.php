<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Actions\Staff;

use App\Domains\HospitalEntry\Dto\Staff\HospitalEntryForStaffDetailDto;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use Illuminate\Support\Facades\Gate;

final class HospitalEntryGetForStaffAction
{
    public function execute(HospitalEntry $hospitalEntry): array
    {
        Gate::authorize('view', $hospitalEntry);

        $hospitalEntry->load([
            'businessRegistrationFile',
            'licenseFile',
        ]);

        return [
            'hospital_entry' => HospitalEntryForStaffDetailDto::fromModel($hospitalEntry)->toArray(),
        ];
    }
}
