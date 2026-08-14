<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions\Staff;

use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Queries\Staff\HospitalWalletDashboardForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalWalletDashboardGetForStaffAction
{
    public function __construct(private readonly HospitalWalletDashboardForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalWallet::class);

        return $this->query->overview((int) $filters['year']);
    }
}
