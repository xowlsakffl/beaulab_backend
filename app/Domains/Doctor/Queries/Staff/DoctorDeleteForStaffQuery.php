<?php

namespace App\Domains\Doctor\Queries\Staff;

use App\Domains\Doctor\Models\Doctor;

final class DoctorDeleteForStaffQuery
{
    public function softDelete(Doctor $doctor): void
    {
        $doctor->delete();
    }
}
