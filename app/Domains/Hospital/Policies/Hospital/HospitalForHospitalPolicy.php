<?php

namespace App\Domains\Hospital\Policies\Hospital;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\Hospital\Models\Hospital;

final class HospitalForHospitalPolicy
{
    public function viewAny(AccountHospital $actor): bool
    {
        return $actor->can(AccessPermissions::HOSPITAL_PROFILE_SHOW);
    }

    public function view(AccountHospital $actor, Hospital $hospital): bool
    {
        return $actor->can(AccessPermissions::HOSPITAL_PROFILE_SHOW) && $this->ownsHospital($actor, $hospital);
    }

    public function create(AccountHospital $actor): bool
    {
        return false;
    }

    public function update(AccountHospital $actor, Hospital $hospital): bool
    {
        return $actor->can(AccessPermissions::HOSPITAL_PROFILE_UPDATE) && $this->ownsHospital($actor, $hospital);
    }

    public function delete(AccountHospital $actor, Hospital $hospital): bool
    {
        return false;
    }

    private function ownsHospital(AccountHospital $actor, Hospital $hospital): bool
    {
        return (int) $actor->hospital_id > 0 && (int) $actor->hospital_id === (int) $hospital->id;
    }
}
