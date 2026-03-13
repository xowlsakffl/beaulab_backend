<?php

namespace App\Domains\Faq\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Faq\Models\Faq;
use App\Domains\Faq\Policies\Staff\FaqForStaffPolicy;

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
