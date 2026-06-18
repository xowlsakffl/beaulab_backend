<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\HospitalEvent\Dto\Staff\HospitalEventRealModelDBForStaffDto;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventRealModelDBImageSummaryQuery;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventRealModelDBListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalEventRealModelDBListForStaffAction
{
    public function __construct(private readonly HospitalEventRealModelDBListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalEvent::class);

        $paginator = $this->query->paginate($filters);
        $imageSummaries = HospitalEventRealModelDBImageSummaryQuery::forApplicationIds(
            $paginator->getCollection()->pluck('id')->all(),
        );

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn ($application): array => HospitalEventRealModelDBForStaffDto::fromModel(
                $application,
                $imageSummaries[(int) $application->id] ?? null,
            )->toArray(),
        );
    }
}
