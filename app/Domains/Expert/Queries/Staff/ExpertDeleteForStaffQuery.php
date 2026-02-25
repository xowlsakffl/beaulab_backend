<?php

namespace App\Domains\Expert\Queries\Staff;

use App\Domains\Expert\Models\Expert;

final class ExpertDeleteForStaffQuery
{
    public function softDelete(Expert $expert): void
    {
        $expert->delete();
    }
}
