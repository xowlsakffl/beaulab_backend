<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Actions\Staff;

use App\Domains\AccountUser\Dto\Staff\AccountUserForStaffDetailDto;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\Staff\AccountUserStatusUpdateForStaffQuery;
use App\Domains\Common\Cache\Support\StaffSummaryCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class AccountUserStatusUpdateForStaffAction
{
    public function __construct(
        private readonly AccountUserStatusUpdateForStaffQuery $query,
        private readonly AccountUserUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    /**
     * @param  array{status:string, reason?:string|null}  $payload
     * @return array{user: array}
     */
    public function execute(AccountUser $user, array $payload): array
    {
        Gate::authorize('updateStatus', $user);

        $updated = DB::transaction(function () use ($user, $payload): AccountUser {
            $user = AccountUser::query()->lockForUpdate()->findOrFail($user->getKey());
            $beforeStatus = (string) $user->status;
            $updatedUser = $this->query->update($user, $payload['status']);
            $this->historyRecordAction->recordStatusUpdated(
                $updatedUser,
                $beforeStatus,
                (string) $updatedUser->status,
                $payload['reason'] ?? null,
            );

            return $updatedUser->fresh();
        });

        StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_ACCOUNT_USER);

        return [
            'user' => AccountUserForStaffDetailDto::fromModel(
                $updated->loadLatestStatusHistory()
            )->toArray(),
        ];
    }
}
