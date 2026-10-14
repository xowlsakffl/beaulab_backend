<?php

namespace App\Domains\AccountUser\Actions\Staff;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\Staff\AccountUserStatisticsForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class AccountUserStatisticsForStaffAction
{
    public function __construct(private readonly AccountUserStatisticsForStaffQuery $query) {}

    public function execute(string $section, string $period = 'daily'): array
    {
        Gate::authorize('viewStatistics', AccountUser::class);

        return match ($section) {
            'summary' => $this->query->summary(),
            'channels' => $this->query->channels(),
            'visits' => $this->query->visits($period),
        };
    }
}
