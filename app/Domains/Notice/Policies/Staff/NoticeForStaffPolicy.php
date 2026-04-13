<?php

namespace App\Domains\Notice\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Notice\Models\Notice;

/**
 * NoticeForStaffPolicy 역할 정의.
 * 공지사항 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class NoticeForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_NOTICE_SHOW);
    }

    public function view(AccountStaff $actor, Notice $notice): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_NOTICE_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_NOTICE_CREATE);
    }

    public function update(AccountStaff $actor, Notice $notice): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_NOTICE_UPDATE);
    }

    public function delete(AccountStaff $actor, Notice $notice): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_NOTICE_DELETE);
    }
}
