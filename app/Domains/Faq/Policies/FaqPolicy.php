<?php

namespace App\Domains\Faq\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Faq\Models\Faq;
use App\Domains\Faq\Policies\Staff\FaqForStaffPolicy;

/**
 * FaqPolicy 역할 정의.
 * FAQ 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class FaqPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, Faq $faq): bool
    {
        return $this->delegate($actor)->view($actor, $faq);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, Faq $faq): bool
    {
        return $this->delegate($actor)->update($actor, $faq);
    }

    public function delete(mixed $actor, Faq $faq): bool
    {
        return $this->delegate($actor)->delete($actor, $faq);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(FaqForStaffPolicy::class),
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, Faq $faq): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, Faq $faq): bool { return false; }
                public function delete(mixed $actor, Faq $faq): bool { return false; }
            },
        };
    }
}
