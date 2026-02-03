<?php

namespace App\Domains\Hospital\Queries\Admin;

use App\Domains\Hospital\Models\Hospital;

final class HospitalDeleteForStaffQuery
{
    /**
     * 병원 삭제 soft delete
     */
    public function softDelete(Hospital $hospital): void
    {
        $hospital->delete();
    }
}
