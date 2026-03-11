<?php

namespace App\Domains\HospitalTalk\Queries\Staff;

use App\Domains\HospitalTalk\Models\HospitalTalk;

final class HospitalTalkDeleteForStaffQuery
{
    public function softDelete(HospitalTalk $talk): void
    {
        $talk->comments()->delete();
        $talk->delete();
    }
}
