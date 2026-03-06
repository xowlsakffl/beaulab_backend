<?php

namespace App\Domains\Common\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Models\Category\Category;

final class CategoryForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_CATEGORY_SHOW);
    }

    public function view(AccountStaff $actor, Category $category): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_CATEGORY_SHOW);
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
