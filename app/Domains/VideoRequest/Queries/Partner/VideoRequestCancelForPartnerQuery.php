<?php

namespace App\Domains\VideoRequest\Queries\Partner;

use App\Domains\VideoRequest\Models\VideoRequest;

final class VideoRequestCancelForPartnerQuery
{
    public function cancel(VideoRequest $videoRequest, array $payload): VideoRequest
    {
        $reviewStatus = $payload['review_status'];

        $videoRequest->fill([
            'review_status' => $reviewStatus,
            'reviewed_by_staff_id' => null,
            'reviewed_at' => null,
            'reject_reason' => $reviewStatus === VideoRequest::REVIEW_STATUS_PARTNER_CANCELED ? 'PARTNER_CANCELED' : null,
            'reject_reason_detail' => $reviewStatus === VideoRequest::REVIEW_STATUS_PARTNER_CANCELED ? '파트너 취소' : null,
        ]);

        if ($videoRequest->isDirty()) {
            $videoRequest->save();
        }

        return $videoRequest->fresh();
    }
}
