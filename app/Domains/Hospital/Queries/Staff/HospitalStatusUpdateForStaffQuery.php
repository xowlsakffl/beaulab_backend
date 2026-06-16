<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Queries\Staff;

use App\Domains\Hospital\Models\Hospital;

final class HospitalStatusUpdateForStaffQuery
{
    public function update(Hospital $hospital, string $status): Hospital
    {
        $hospital->status = $status;

        if ($hospital->isDirty('status')) {
            $hospital->save();
        }

        return $hospital->fresh();
    }
}
