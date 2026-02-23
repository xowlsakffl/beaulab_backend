<?php

namespace App\Domains\Common\Queries\BusinessRegistration;

use App\Domains\Common\Models\BusinessRegistration;

final class BusinessRegistrationCreateForStaffQuery
{
    public function create(array $data): BusinessRegistration
    {
        return BusinessRegistration::create($data);
    }
}
