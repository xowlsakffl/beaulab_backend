<?php

namespace App\Domains\VideoRequest\Policies\Partner;

use App\Domains\Partner\Models\AccountPartner;
use App\Domains\VideoRequest\Models\VideoRequest;

final class VideoRequestForPartnerPolicy
{
    public function viewAny(AccountPartner $actor): bool
    {
        return $actor->can('common.access');
    }

    public function view(AccountPartner $actor, VideoRequest $videoRequest): bool
    {
        return $this->isOwner($actor, $videoRequest);
    }

    public function create(AccountPartner $actor): bool
    {
        return $actor->can('common.access');
    }

    public function update(AccountPartner $actor, VideoRequest $videoRequest): bool
    {
        return $this->isOwner($actor, $videoRequest) && $videoRequest->review_status === VideoRequest::REVIEW_STATUS_PENDING;
    }

    public function delete(AccountPartner $actor, VideoRequest $videoRequest): bool
    {
        return $this->isOwner($actor, $videoRequest) && $videoRequest->review_status === VideoRequest::REVIEW_STATUS_PENDING;
    }

    private function isOwner(AccountPartner $actor, VideoRequest $videoRequest): bool
    {
        return (int) $videoRequest->submitted_by_partner_id === (int) $actor->id;
    }
}
