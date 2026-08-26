<?php

namespace App\Domains\Common\ContentReport\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\ContentReport\Policies\Staff\ContentReportStateForStaffPolicy;

final class ContentReportStatePolicy
{
    public function viewAny(mixed $actor, ?string $targetAlias = null): bool
    {
        return $this->delegate($actor)->viewAny($actor, $targetAlias);
    }

    public function update(mixed $actor, ?string $targetAlias = null): bool
    {
        return $this->delegate($actor)->update($actor, $targetAlias);
    }

    public function updateStatus(mixed $actor, ?string $targetAlias = null): bool
    {
        return $actor instanceof AccountStaff
            && app(ContentReportStateForStaffPolicy::class)->updateStatus($actor, $targetAlias);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(ContentReportStateForStaffPolicy::class),
            default => new class
            {
                public function viewAny(mixed $actor, ?string $targetAlias = null): bool
                {
                    return false;
                }

                public function update(mixed $actor, ?string $targetAlias = null): bool
                {
                    return false;
                }
            },
        };
    }
}
