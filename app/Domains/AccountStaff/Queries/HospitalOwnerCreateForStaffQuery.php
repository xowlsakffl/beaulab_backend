<?php

namespace App\Domains\AccountStaff\Queries;

use App\Domains\AccountHospital\Models\AccountHospital;

final class HospitalOwnerCreateForStaffQuery
{
    public function create(array $data): AccountHospital
    {
        return AccountHospital::create($data);
    }
}
