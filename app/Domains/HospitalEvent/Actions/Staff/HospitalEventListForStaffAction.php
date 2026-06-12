<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\HospitalEvent\Dto\Staff\HospitalEventForStaffDto;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalEventListForStaffAction
{
    public function __construct(private readonly HospitalEventListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalEvent::class);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn ($event): array => HospitalEventForStaffDto::fromModel($event)->toArray(),
        );
    }
}
