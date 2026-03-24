<?php

namespace App\Domains\HospitalVideo\Queries\Hospital;

use App\Domains\HospitalVideo\Models\HospitalVideo;

final class HospitalVideoCancelForHospitalQuery
{
    public function cancel(HospitalVideo $video): HospitalVideo
    {
        $video->fill([
            'status' => HospitalVideo::STATUS_INACTIVE,
            'allow_status' => HospitalVideo::ALLOW_STATUS_PARTNER_CANCELED,
        ]);

        if ($video->isDirty()) {
            $video->save();
        }

        return $video->fresh();
    }
}
