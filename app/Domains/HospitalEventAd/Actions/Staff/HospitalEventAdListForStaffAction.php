<?php

namespace App\Domains\HospitalEventAd\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\HospitalEventAd\Dto\Staff\HospitalEventAdForStaffDto;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalEventAd\Queries\Staff\HospitalEventAdListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalEventAdListForStaffAction
{
    public function __construct(private readonly HospitalEventAdListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalEventAd::class);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn ($ad): array => HospitalEventAdForStaffDto::fromModel($ad)->toArray(),
        );
    }
}
