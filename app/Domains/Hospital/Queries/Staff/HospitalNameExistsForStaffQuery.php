<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Queries\Staff;

use App\Domains\Hospital\Models\Hospital;

final class HospitalNameExistsForStaffQuery
{
    public function exists(string $name): bool
    {
        return Hospital::query()
            ->where('name', $name)
            ->exists();
    }
}
