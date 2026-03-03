<?php

namespace App\Domains\HospitalBusinessRegistration\Queries;

use App\Domains\HospitalBusinessRegistration\Models\HospitalBusinessRegistration;

final class HospitalBusinessRegistrationCreateForStaffQuery
{
    public function create(array $data): HospitalBusinessRegistration
    {
        return HospitalBusinessRegistration::create($data);
    }
}
