<?php

namespace App\Domains\HospitalEvaluation\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalEvaluation\Policies\Staff\HospitalEvaluationForStaffPolicy;

final class HospitalEvaluationPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, HospitalEvaluation $evaluation): bool
    {
        return $this->delegate($actor)->view($actor, $evaluation);
    }

    public function update(mixed $actor, ?HospitalEvaluation $evaluation = null): bool
    {
        return $this->delegate($actor)->update($actor, $evaluation);
    }

    public function updateStatus(mixed $actor, ?HospitalEvaluation $evaluation = null): bool
    {
        return $actor instanceof AccountStaff
            && app(HospitalEvaluationForStaffPolicy::class)->updateStatus($actor, $evaluation);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(HospitalEvaluationForStaffPolicy::class),
            $actor instanceof AccountUser => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, HospitalEvaluation $evaluation): bool
                {
                    return false;
                }

                public function update(mixed $actor, ?HospitalEvaluation $evaluation = null): bool
                {
                    return false;
                }
            },
            default => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, HospitalEvaluation $evaluation): bool
                {
                    return false;
                }

                public function update(mixed $actor, ?HospitalEvaluation $evaluation = null): bool
                {
                    return false;
                }
            },
        };
    }
}
