<?php

namespace App\Domains\HospitalVideo\Queries\Staff;

use App\Domains\HospitalVideo\Models\HospitalVideo;

final class HospitalVideoDeleteForStaffQuery
{
    public function softDelete(HospitalVideo $video): void
    {
        $video->delete();
    }
}
