<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions\Staff;

use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Queries\Staff\HospitalWalletDashboardForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalWalletTopHospitalsForStaffAction
{
    public function __construct(private readonly HospitalWalletDashboardForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalWallet::class);

        return [
            'items' => $this->query->topHospitals($filters),
            'filters' => [
                'balance_type' => $filters['balance_type'],
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date'],
            ],
        ];
    }
}
