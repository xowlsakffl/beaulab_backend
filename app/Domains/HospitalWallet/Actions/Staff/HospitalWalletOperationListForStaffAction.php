<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\HospitalWallet\Dto\Staff\HospitalWalletOperationForStaffDto;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Domains\HospitalWallet\Queries\Staff\HospitalWalletOperationListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalWalletOperationListForStaffAction
{
    public function __construct(
        private readonly HospitalWalletOperationListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewHistory', HospitalWallet::class);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn (HospitalWalletOperation $operation): array => HospitalWalletOperationForStaffDto::fromModel($operation)->toArray(),
            [
                'type_groups' => HospitalWalletOperation::typeGroupOptions(),
                'charge_statuses' => HospitalWalletOperation::chargeStatusOptions(),
                'usage_included' => $this->query->includesUsage($filters),
            ],
        );
    }
}
