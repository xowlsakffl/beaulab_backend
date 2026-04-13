<?php

namespace App\Domains\Common\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Models\Category\Category;

/**
 * CategoryForStaffPolicy 역할 정의.
 * 공통 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class CategoryForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::COMMON_ACCESS);
    }

    public function view(AccountStaff $actor, Category $category): bool
    {
        return $actor->can(AccessPermissions::COMMON_ACCESS);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_CATEGORY_MANAGE);
    }

    public function update(AccountStaff $actor, Category $category): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_CATEGORY_MANAGE);
    }

    public function delete(AccountStaff $actor, Category $category): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_CATEGORY_MANAGE);
    }
}
