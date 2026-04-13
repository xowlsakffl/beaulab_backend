<?php

namespace App\Domains\HospitalVideo\Policies\Hospital;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\HospitalVideo\Models\HospitalVideo;

/**
 * HospitalVideoForHospitalPolicy 역할 정의.
 * 병원 동영상 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class HospitalVideoForHospitalPolicy
{
    public function viewAny(AccountHospital $actor): bool
    {
        return $actor->can(AccessPermissions::HOSPITAL_VIDEO_SHOW);
    }

    public function view(AccountHospital $actor, HospitalVideo $video): bool
    {
        return $actor->can(AccessPermissions::HOSPITAL_VIDEO_SHOW) && $this->ownsVideo($actor, $video);
    }

    public function create(AccountHospital $actor): bool
    {
        return $actor->can(AccessPermissions::HOSPITAL_VIDEO_CREATE);
    }

    public function update(AccountHospital $actor, HospitalVideo $video): bool
    {
        return $actor->can(AccessPermissions::HOSPITAL_VIDEO_UPDATE) && $this->ownsVideo($actor, $video);
    }

    public function delete(AccountHospital $actor, HospitalVideo $video): bool
    {
        return false;
    }

    public function cancel(AccountHospital $actor, HospitalVideo $video): bool
    {
        return $actor->can(AccessPermissions::HOSPITAL_VIDEO_CANCEL) && $this->ownsVideo($actor, $video);
    }

    private function ownsVideo(AccountHospital $actor, HospitalVideo $video): bool
    {
        return (int) $actor->hospital_id > 0 && (int) $actor->hospital_id === (int) $video->hospital_id;
    }
}
