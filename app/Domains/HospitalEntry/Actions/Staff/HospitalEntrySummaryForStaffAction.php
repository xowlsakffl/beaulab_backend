<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Actions\Staff;

use App\Domains\HospitalEntry\Dto\Staff\HospitalEntrySummaryForStaffDto;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use App\Domains\HospitalEntry\Queries\Staff\HospitalEntrySummaryForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalEntrySummaryForStaffAction
{
    public function __construct(
        private readonly HospitalEntrySummaryForStaffQuery $query,
    ) {}

    /**
     * @return array<string, int>
     */
    public function execute(): array
    {
        Gate::authorize('viewAny', HospitalEntry::class);

        return HospitalEntrySummaryForStaffDto::fromArray($this->query->get())->toArray();
    }
}
