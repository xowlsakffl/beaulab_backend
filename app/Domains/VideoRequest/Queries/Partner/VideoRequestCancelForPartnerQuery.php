<?php

namespace App\Domains\VideoRequest\Queries\Partner;

use App\Domains\VideoRequest\Models\VideoRequest;

final class VideoRequestCancelForPartnerQuery
{
    public function cancel(VideoRequest $videoRequest): VideoRequest
    {
        $videoRequest->review_status = VideoRequest::REVIEW_STATUS_PARTNER_CANCELED;
        $videoRequest->save();

        return $videoRequest->fresh();
    }
}
