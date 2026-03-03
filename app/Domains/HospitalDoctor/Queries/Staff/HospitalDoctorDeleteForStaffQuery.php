<?php

namespace App\Domains\HospitalDoctor\Queries\Staff;

use App\Domains\HospitalDoctor\Models\HospitalDoctor;

final class HospitalDoctorDeleteForStaffQuery
{
    public function softDelete(HospitalDoctor $doctor): void
    {
        $doctor->delete();
    }
}
