<?php

namespace App\Domains\VideoRequest\Policies\Partner;

use App\Common\Authorization\AccessPermissions;
use App\Domains\Partner\Models\AccountPartner;
use App\Domains\VideoRequest\Models\VideoRequest;

final class VideoRequestForPartnerPolicy
{
    public function viewAny(AccountPartner $actor): bool
    {
        if ($actor->isHospital()) {
            return $actor->can(AccessPermissions::HOSPITAL_VIDEO_REQUEST_SHOW);
        }

        if ($actor->isBeauty()) {
            return $actor->can(AccessPermissions::BEAUTY_VIDEO_REQUEST_SHOW);
        }

        return false;
    }

    public function view(AccountPartner $actor, VideoRequest $videoRequest): bool
    {
        if ($actor->isHospital()) {
            return $actor->can(AccessPermissions::HOSPITAL_VIDEO_REQUEST_SHOW)
                && $videoRequest->isAccessibleByPartner($actor);
        }

        if ($actor->isBeauty()) {
            return $actor->can(AccessPermissions::BEAUTY_VIDEO_REQUEST_SHOW)
                && $videoRequest->isAccessibleByPartner($actor);
        }

        return false;
    }

    public function create(AccountPartner $actor): bool
    {
        if ($actor->isHospital()) {
            return $actor->can(AccessPermissions::HOSPITAL_VIDEO_REQUEST_CREATE)
                && (int) $actor->hospital_id > 0;
        }

        if ($actor->isBeauty()) {
            return $actor->can(AccessPermissions::BEAUTY_VIDEO_REQUEST_CREATE)
                && (int) $actor->beauty_id > 0;
        }

        return false;
    }

    public function update(AccountPartner $actor, VideoRequest $videoRequest): bool
    {
        if ($actor->isHospital()) {
            return $actor->can(AccessPermissions::HOSPITAL_VIDEO_REQUEST_UPDATE)
                && $videoRequest->isAccessibleByPartner($actor);
        }

        if ($actor->isBeauty()) {
            return $actor->can(AccessPermissions::BEAUTY_VIDEO_REQUEST_UPDATE)
                && $videoRequest->isAccessibleByPartner($actor);
        }

        return false;
    }

    public function delete(AccountPartner $actor, VideoRequest $videoRequest): bool
    {
        return false;
    }

    public function cancel(AccountPartner $actor, VideoRequest $videoRequest): bool
    {
        if ($actor->isHospital()) {
            return $actor->can(AccessPermissions::HOSPITAL_VIDEO_REQUEST_CANCEL)
                && $videoRequest->isAccessibleByPartner($actor);
        }

        if ($actor->isBeauty()) {
            return $actor->can(AccessPermissions::BEAUTY_VIDEO_REQUEST_CANCEL)
                && $videoRequest->isAccessibleByPartner($actor);
        }

        return false;
    }
}
