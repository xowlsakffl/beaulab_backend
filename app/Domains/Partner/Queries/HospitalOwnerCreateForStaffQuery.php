<?php

namespace App\Domains\Partner\Queries;

use App\Domains\Hospital\Models\AccountHospital;

final class HospitalOwnerCreateForStaffQuery
{
    public function create(array $data): AccountHospital
    {
        return AccountHospital::create($data);
    }
}
