<?php

namespace App\Domains\BeautyBusinessRegistration\Queries;

use App\Domains\BeautyBusinessRegistration\Models\BeautyBusinessRegistration;

final class BeautyBusinessRegistrationCreateForStaffQuery
{
    public function create(array $data): BeautyBusinessRegistration
    {
        return BeautyBusinessRegistration::create($data);
    }
}
