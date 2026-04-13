<?php

namespace App\Domains\HospitalDoctor\Policies;

use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalDoctor\Policies\Staff\HospitalDoctorForStaffPolicy;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;

/**
 * HospitalDoctorPolicy 역할 정의.
 * 병원 의사 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class HospitalDoctorPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, HospitalDoctor $doctor): bool
    {
        return $this->delegate($actor)->view($actor, $doctor);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, HospitalDoctor $doctor): bool
    {
        return $this->delegate($actor)->update($actor, $doctor);
    }

    public function delete(mixed $actor, HospitalDoctor $doctor): bool
    {
        return $this->delegate($actor)->delete($actor, $doctor);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(HospitalDoctorForStaffPolicy::class),
            //$actor instanceof AccountPartner,
            $actor instanceof AccountUser => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, HospitalDoctor $doctor): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, HospitalDoctor $doctor): bool { return false; }
                public function delete(mixed $actor, HospitalDoctor $doctor): bool { return false; }
            },
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, HospitalDoctor $doctor): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, HospitalDoctor $doctor): bool { return false; }
                public function delete(mixed $actor, HospitalDoctor $doctor): bool { return false; }
            },
        };
    }
}
