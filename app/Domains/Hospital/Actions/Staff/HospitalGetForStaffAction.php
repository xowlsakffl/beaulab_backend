<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Hospital\Dto\Staff\HospitalForStaffDetailDto;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Support\Facades\Gate;

final class HospitalGetForStaffAction
{
    /**
     * @param array<int, string> $include
     * @return array{hospital: array}
     */
    public function execute(Hospital $hospital, array $include = [], string $ability = 'view'): array
    {
        Gate::authorize($ability, $hospital);

        $relations = ['logoMedia', 'galleryMedia', 'categories', 'features'];

        if (in_array('business_registration', $include, true)) {
            $relations[] = 'businessRegistration.certificateMedia';
        }

        if (in_array('account_hospitals', $include, true)) {
            $relations[] = 'accountHospitals.roles';
        }

        if (in_array('doctors', $include, true)) {
            Gate::authorize('viewAny', HospitalDoctor::class);
            $relations[] = 'doctors';
        }

        if ($relations !== []) {
            $hospital->load($relations);
        }

        return [
            'hospital' => HospitalForStaffDetailDto::fromModel($hospital, $include)->toArray(),
        ];
    }
}
