<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\HospitalEvent\Dto\Staff\HospitalEventSummaryForStaffDto;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventSummaryForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalEventSummaryForStaffAction
{
    public function __construct(
        private readonly HospitalEventSummaryForStaffQuery $query,
    ) {}

    /**
     * @return array<string, int>
     */
    public function execute(): array
    {
        Gate::authorize('viewAny', HospitalEvent::class);

        return HospitalEventSummaryForStaffDto::fromArray($this->query->get())->toArray();
    }
}
