<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\HospitalWallet\Dto\Staff\HospitalWalletForStaffDto;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Queries\Staff\HospitalWalletListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalWalletListForStaffAction
{
    public function __construct(private readonly HospitalWalletListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalWallet::class);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn (HospitalWallet $wallet): array => HospitalWalletForStaffDto::fromModel($wallet)->toArray(),
        );
    }
}
