<?php

namespace App\Domains\HospitalVideoRequest\Policies\Hospital;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;

final class HospitalVideoRequestForHospitalPolicy
{
    public function viewAny(AccountHospital $actor): bool
    {
        return $actor->can(AccessPermissions::HOSPITAL_VIDEO_REQUEST_SHOW);
    }

    public function view(AccountHospital $actor, HospitalVideoRequest $videoRequest): bool
    {
        return $actor->can(AccessPermissions::HOSPITAL_VIDEO_REQUEST_SHOW)
            && (int) $videoRequest->hospital_id === (int) $actor->hospital_id;
    }

    public function create(AccountHospital $actor): bool
    {
        return $actor->can(AccessPermissions::HOSPITAL_VIDEO_REQUEST_CREATE);
    }

    public function update(AccountHospital $actor, HospitalVideoRequest $videoRequest): bool
    {
        return $actor->can(AccessPermissions::HOSPITAL_VIDEO_REQUEST_UPDATE)
            && (int) $videoRequest->hospital_id === (int) $actor->hospital_id;
    }

    public function delete(AccountHospital $actor, HospitalVideoRequest $videoRequest): bool
    {
        unset($videoRequest);

        return false;
    }

    public function cancel(AccountHospital $actor, HospitalVideoRequest $videoRequest): bool
    {
        return $actor->can(AccessPermissions::HOSPITAL_VIDEO_REQUEST_CANCEL)
            && (int) $videoRequest->hospital_id === (int) $actor->hospital_id;
    }
}
