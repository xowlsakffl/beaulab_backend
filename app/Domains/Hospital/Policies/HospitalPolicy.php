<?php

namespace App\Domains\Hospital\Policies;

use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Policies\Hospital\HospitalForHospitalPolicy;
use App\Domains\Hospital\Policies\Staff\HospitalForStaffPolicy;

/**
 * HospitalPolicy 역할 정의.
 * 병원 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class HospitalPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, Hospital $hospital): bool
    {
        return $this->delegate($actor)->view($actor, $hospital);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, Hospital $hospital): bool
    {
        return $this->delegate($actor)->update($actor, $hospital);
    }

    public function delete(mixed $actor, Hospital $hospital): bool
    {
        return $this->delegate($actor)->delete($actor, $hospital);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff   => app(HospitalForStaffPolicy::class),
            $actor instanceof AccountHospital => app(HospitalForHospitalPolicy::class),
            //$actor instanceof AccountUser    => app(HospitalForUserPolicy::class),
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, Hospital $hospital): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, Hospital $hospital): bool { return false; }
                public function delete(mixed $actor, Hospital $hospital): bool { return false; }
            },
        };
    }
}
