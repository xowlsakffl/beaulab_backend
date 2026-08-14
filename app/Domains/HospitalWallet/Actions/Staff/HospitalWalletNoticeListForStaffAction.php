<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Common\Sms\Models\SmsBatch;
use App\Domains\HospitalWallet\Dto\Staff\HospitalWalletNoticeBatchForStaffDto;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Queries\Staff\HospitalWalletNoticeListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalWalletNoticeListForStaffAction
{
    public function __construct(
        private readonly HospitalWalletNoticeListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewNotices', HospitalWallet::class);

        return PaginatedResponse::fromPaginator(
            $this->query->paginate($filters),
            fn (SmsBatch $batch): array => HospitalWalletNoticeBatchForStaffDto::fromModel($batch)->toArray(),
            [
                'statuses' => collect(SmsBatch::statuses())
                    ->map(static fn (string $status): array => [
                        'value' => $status,
                        'label' => SmsBatch::statusLabel($status),
                    ])
                    ->all(),
            ],
        );
    }
}
