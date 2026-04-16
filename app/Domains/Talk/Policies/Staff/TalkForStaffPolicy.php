<?php

namespace App\Domains\Talk\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Talk\Models\Talk;

/**
 * TalkForStaffPolicy 역할 정의.
 * 토크 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class TalkForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_SHOW);
    }

    public function view(AccountStaff $actor, Talk $talk): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_CREATE);
    }

    public function update(AccountStaff $actor, ?Talk $talk = null): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_UPDATE);
    }

    public function delete(AccountStaff $actor, Talk $talk): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_DELETE);
    }
}
