<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\AccountHospital\Dto\Staff\HospitalAccountInvitationForStaffDto;
use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use App\Domains\AccountHospital\Queries\Staff\HospitalAccountInvitationForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalAccountInvitationListForStaffAction
{
    public function __construct(
        private readonly HospitalAccountInvitationForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalAccountInvitation::class);
        Gate::authorize('view', $this->query->findSource(
            (string) $filters['source_type'],
            (int) $filters['source_id'],
        ));

        $paginator = $this->query->paginate(
            (string) $filters['source_type'],
            (int) $filters['source_id'],
            (int) $filters['per_page'],
        );

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn (HospitalAccountInvitation $invitation): array => HospitalAccountInvitationForStaffDto::fromModel($invitation),
        );
    }
}
