<?php

namespace App\Domains\Beauty\Policies\Partner;

use App\Domains\Beauty\Models\Beauty;
use App\Domains\Staff\Models\AccountStaff;

final class BeautyForPartnerPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can('beaulab.beauty.list');
    }

    public function view(AccountStaff $actor, Beauty $beauty): bool
    {
        return $actor->can('beaulab.beauty.show');
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
