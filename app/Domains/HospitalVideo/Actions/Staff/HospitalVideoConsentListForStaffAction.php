<?php

declare(strict_types=1);

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\HospitalVideo\Dto\Staff\HospitalVideoConsentForStaffDto;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\HospitalVideo\Queries\Staff\HospitalVideoConsentListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoConsentListForStaffAction
{
    public function __construct(private readonly HospitalVideoConsentListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalVideo::class);

        return PaginatedResponse::fromPaginator(
            $this->query->paginate($filters),
            fn ($hospital): array => HospitalVideoConsentForStaffDto::fromModel($hospital)->toArray(),
        );
    }
}
