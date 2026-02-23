<?php

namespace App\Domains\Hospital\Policies\Partner;

use App\Domains\Hospital\Models\Hospital;
use App\Domains\Staff\Models\AccountStaff;

final class HospitalForPartnerPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can('beaulab.hospital.list');
    }

    public function view(AccountStaff $actor, Hospital $hospital): bool
    {
        return $actor->can('beaulab.hospital.show');
    }
    public function create(AccountStaff $actor): bool
    {
        return $actor->can('beaulab.hospital.create');
    }

    public function update(AccountStaff $actor, Hospital $hospital): bool
    {
        return $actor->can('beaulab.hospital.update');
    }

    public function delete(AccountStaff $actor, Hospital $hospital): bool
    {
        return $actor->can('beaulab.hospital.delete');
    }
}
