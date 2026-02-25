<?php

namespace App\Domains\Expert\Policies\Staff;

use App\Domains\Expert\Models\Expert;
use App\Domains\Staff\Models\AccountStaff;

final class ExpertForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can('beaulab.expert.show');
    }

    public function view(AccountStaff $actor, Expert $expert): bool
    {
        return $actor->can('beaulab.expert.show');
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can('beaulab.expert.create');
    }

    public function update(AccountStaff $actor, Expert $expert): bool
    {
        return $actor->can('beaulab.expert.update');
    }

    public function delete(AccountStaff $actor, Expert $expert): bool
    {
        return $actor->can('beaulab.expert.delete');
    }
}
