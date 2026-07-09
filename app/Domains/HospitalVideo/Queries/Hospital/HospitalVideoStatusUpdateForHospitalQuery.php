<?php

namespace App\Domains\HospitalVideo\Queries\Hospital;

use App\Domains\HospitalVideo\Models\HospitalVideo;

final class HospitalVideoStatusUpdateForHospitalQuery
{
    public function update(HospitalVideo $video, string $hospitalStatus): HospitalVideo
    {
        $video->fill([
            'hospital_status' => $hospitalStatus,
        ]);

        if ($video->isDirty()) {
            $video->save();
        }

        return $video->fresh();
    }
}
