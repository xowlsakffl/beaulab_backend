<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\HospitalEvent\Dto\Staff\HospitalEventConsultationForStaffDto;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventConsultationListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalEventConsultationListForStaffAction
{
    public function __construct(private readonly HospitalEventConsultationListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalEvent::class);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn ($consultation): array => HospitalEventConsultationForStaffDto::fromModel($consultation)->toArray(),
        );
    }
}
