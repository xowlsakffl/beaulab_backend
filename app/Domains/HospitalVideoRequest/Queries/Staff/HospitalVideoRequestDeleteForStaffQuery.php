<?php

namespace App\Domains\HospitalVideoRequest\Queries\Staff;

use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;

final class HospitalVideoRequestDeleteForStaffQuery
{
    public function softDelete(HospitalVideoRequest $videoRequest): void
    {
        $videoRequest->delete();
    }
}
