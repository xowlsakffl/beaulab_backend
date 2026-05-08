<?php

namespace App\Domains\HospitalEvaluation\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;

final class HospitalEvaluationForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVALUATION_SHOW);
    }

    public function view(AccountStaff $actor, HospitalEvaluation $evaluation): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVALUATION_SHOW);
    }

    public function update(AccountStaff $actor, ?HospitalEvaluation $evaluation = null): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVALUATION_UPDATE);
    }
}
