<?php

namespace App\Domains\Faq\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Faq\Models\Faq;

/**
 * FaqForStaffPolicy 역할 정의.
 * FAQ 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class FaqForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_FAQ_SHOW);
    }

    public function view(AccountStaff $actor, Faq $faq): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_FAQ_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_FAQ_CREATE);
    }

    public function update(AccountStaff $actor, Faq $faq): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_FAQ_UPDATE);
    }

    public function delete(AccountStaff $actor, Faq $faq): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_FAQ_DELETE);
    }
}
