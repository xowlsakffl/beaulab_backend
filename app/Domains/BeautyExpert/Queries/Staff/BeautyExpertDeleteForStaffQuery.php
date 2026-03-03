<?php

namespace App\Domains\BeautyExpert\Queries\Staff;

use App\Domains\BeautyExpert\Models\BeautyExpert;

final class BeautyExpertDeleteForStaffQuery
{
    public function softDelete(BeautyExpert $expert): void
    {
        $expert->delete();
    }
}
