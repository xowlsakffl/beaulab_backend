<?php

namespace App\Domains\VideoRequest\Policies\Partner;

use App\Common\Authorization\AccessPermissions;
use App\Domains\Partner\Models\AccountPartner;
use App\Domains\VideoRequest\Models\VideoRequest;

final class VideoRequestForPartnerPolicy
{
    public function viewAny(AccountPartner $actor): bool
    {
        return $this->hasPermission($actor, 'show');
    }

    public function view(AccountPartner $actor, VideoRequest $videoRequest): bool
    {
        return $this->hasPermission($actor, 'show')
            && $videoRequest->isOwnedByPartner($actor);
    }

    public function create(AccountPartner $actor): bool
    {
        return $this->hasPermission($actor, 'create');
    }

    public function update(AccountPartner $actor, VideoRequest $videoRequest): bool
    {
        return $this->hasPermission($actor, 'update')
            && $videoRequest->isOwnedByPartner($actor)
            && $videoRequest->isPending();
    }

    public function delete(AccountPartner $actor, VideoRequest $videoRequest): bool
    {
        return false;
    }

    public function cancel(AccountPartner $actor, VideoRequest $videoRequest): bool
    {
        return $this->hasPermission($actor, 'cancel')
            && $videoRequest->isOwnedByPartner($actor)
            && $videoRequest->isPending();
    }

    private function permissionFor(AccountPartner $actor, string $action): ?string
    {
        if ($actor->isHospital()) {
            return match ($action) {
                'show' => AccessPermissions::HOSPITAL_VIDEO_REQUEST_SHOW,
                'create' => AccessPermissions::HOSPITAL_VIDEO_REQUEST_CREATE,
                'update' => AccessPermissions::HOSPITAL_VIDEO_REQUEST_UPDATE,
                'cancel' => AccessPermissions::HOSPITAL_VIDEO_REQUEST_CANCEL,
                default => AccessPermissions::HOSPITAL_VIDEO_REQUEST_SHOW,
            };
        }

        if ($actor->isBeauty()) {
            return match ($action) {
                'show' => AccessPermissions::BEAUTY_VIDEO_REQUEST_SHOW,
                'create' => AccessPermissions::BEAUTY_VIDEO_REQUEST_CREATE,
                'update' => AccessPermissions::BEAUTY_VIDEO_REQUEST_UPDATE,
                'cancel' => AccessPermissions::BEAUTY_VIDEO_REQUEST_CANCEL,
                default => null,
            };
        }

        return null;
    }

    private function hasPermission(AccountPartner $actor, string $action): bool
    {
        $permission = $this->permissionFor($actor, $action);

        return is_string($permission) && $permission !== '' && $actor->can($permission);
    }

}
