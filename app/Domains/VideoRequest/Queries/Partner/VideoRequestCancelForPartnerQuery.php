<?php

namespace App\Domains\VideoRequest\Queries\Partner;

use App\Domains\VideoRequest\Models\VideoRequest;

final class VideoRequestCancelForPartnerQuery
{
    public function cancel(VideoRequest $videoRequest): VideoRequest
    {
        $videoRequest->fill([
            'review_status' => VideoRequest::REVIEW_STATUS_PARTNER_CANCELED,
            'reviewed_by_staff_id' => null,
            'reviewed_at' => null,
            'reject_reason' => 'PARTNER_CANCELED',
            'reject_reason_detail' => '파트너 취소',
        ]);

        if ($videoRequest->isDirty()) {
            $videoRequest->save();
        }

        return $videoRequest->fresh();
    }
}
