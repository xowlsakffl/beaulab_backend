<?php

namespace App\Domains\HospitalVideo\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalVideo\Models\HospitalVideo;

/**
 * HospitalVideoForStaffPolicy 역할 정의.
 * 병원 동영상 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class HospitalVideoForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_SHOW);
    }

    public function view(AccountStaff $actor, HospitalVideo $video): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_CREATE);
    }

    public function update(AccountStaff $actor, HospitalVideo $video): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_UPDATE);
    }

    public function delete(AccountStaff $actor, HospitalVideo $video): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_DELETE);
    }

    public function cancel(AccountStaff $actor, HospitalVideo $video): bool
    {
        return false;
    }
}
