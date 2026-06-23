<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEntry\Dto\Staff\HospitalEntryForStaffDto;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use App\Domains\HospitalEntry\Queries\Staff\HospitalEntryListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalEntryListForStaffAction
{
    public function __construct(private readonly HospitalEntryListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Hospital::class);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn (HospitalEntry $entry): array => HospitalEntryForStaffDto::fromModel($entry)->toArray(),
        );
    }
}
