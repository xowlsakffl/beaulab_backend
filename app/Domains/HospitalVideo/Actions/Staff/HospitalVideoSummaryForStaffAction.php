<?php

declare(strict_types=1);

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Domains\HospitalVideo\Dto\Staff\HospitalVideoSummaryForStaffDto;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\HospitalVideo\Queries\Staff\HospitalVideoSummaryForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoSummaryForStaffAction
{
    public function __construct(
        private readonly HospitalVideoSummaryForStaffQuery $query,
    ) {}

    /**
     * @return array<string, int>
     */
    public function execute(): array
    {
        Gate::authorize('viewAny', HospitalVideo::class);

        return HospitalVideoSummaryForStaffDto::fromArray($this->query->get())->toArray();
    }
}
