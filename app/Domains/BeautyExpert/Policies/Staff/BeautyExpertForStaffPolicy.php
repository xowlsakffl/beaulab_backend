<?php

namespace App\Domains\BeautyExpert\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\AccountStaff\Models\AccountStaff;

/**
 * BeautyExpertForStaffPolicy 역할 정의.
 * 뷰티 전문가 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class BeautyExpertForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_EXPERT_SHOW);
    }

    public function view(AccountStaff $actor, BeautyExpert $expert): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_EXPERT_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_EXPERT_CREATE);
    }

    public function update(AccountStaff $actor, BeautyExpert $expert): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_EXPERT_UPDATE);
    }

    public function delete(AccountStaff $actor, BeautyExpert $expert): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_EXPERT_DELETE);
    }
}
