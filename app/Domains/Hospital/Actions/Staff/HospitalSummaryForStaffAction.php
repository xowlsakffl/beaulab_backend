<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Hospital\Dto\Staff\HospitalSummaryForStaffDto;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalSummaryForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalSummaryForStaffAction
{
    public function __construct(
        private readonly HospitalSummaryForStaffQuery $query,
    ) {}

    /**
     * @return array<string, int>
     */
    public function execute(): array
    {
        Gate::authorize('viewAny', Hospital::class);

        return HospitalSummaryForStaffDto::fromArray($this->query->get())->toArray();
    }
}
