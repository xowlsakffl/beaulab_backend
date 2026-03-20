<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Queries\Staff;

use App\Domains\HospitalBusinessRegistration\Models\HospitalBusinessRegistration;

final class HospitalBusinessNumberExistsForStaffQuery
{
    public function exists(string $businessNumber): bool
    {
        return HospitalBusinessRegistration::query()
            ->where('business_number', $businessNumber)
            ->exists();
    }
}
