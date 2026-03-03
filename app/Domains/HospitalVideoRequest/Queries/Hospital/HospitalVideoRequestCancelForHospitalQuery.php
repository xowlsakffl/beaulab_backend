<?php

namespace App\Domains\HospitalVideoRequest\Queries\Hospital;

use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;

final class HospitalVideoRequestCancelForHospitalQuery
{
    public function cancel(HospitalVideoRequest $videoRequest): HospitalVideoRequest
    {
        $videoRequest->review_status = HospitalVideoRequest::REVIEW_STATUS_PARTNER_CANCELED;
        $videoRequest->save();

        return $videoRequest->fresh();
    }
}
