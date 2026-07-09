<?php

namespace App\Domains\HospitalVideo\Queries\Hospital;

use App\Domains\HospitalVideo\Models\HospitalVideo;

final class HospitalVideoStatusUpdateForHospitalQuery
{
    public function updateHospitalStatus(HospitalVideo $video, string $hospitalStatus): HospitalVideo
    {
        $video->hospital_status = $hospitalStatus;

        if ($video->isDirty('hospital_status')) {
            $video->save();
        }

        return $video->fresh();
    }
}
