<?php

namespace App\Domains\Beauty\Policies\User;

use App\Domains\Beauty\Models\Beauty;
use App\Domains\Staff\Models\AccountStaff;

final class BeautyForUserPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can('beaulab.beauty.list');
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can('beaulab.beauty.create');
    }

    public function update(AccountStaff $actor, Beauty $beauty): bool
    {
        return $actor->can('beaulab.beauty.update');
    }

    public function delete(AccountStaff $actor, Beauty $beauty): bool
    {
        return $actor->can('beaulab.beauty.delete');
    }
}
