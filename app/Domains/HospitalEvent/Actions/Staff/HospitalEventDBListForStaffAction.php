<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\HospitalEvent\Dto\Staff\HospitalEventDBForStaffDto;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventDBListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalEventDBListForStaffAction
{
    public function __construct(private readonly HospitalEventDBListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalEventDB::class);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn ($eventDB): array => HospitalEventDBForStaffDto::fromModel($eventDB)->toArray(),
        );
    }
}
