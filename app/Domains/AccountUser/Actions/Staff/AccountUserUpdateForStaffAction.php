<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Actions\Staff;

use App\Domains\AccountUser\Dto\Staff\AccountUserForStaffDetailDto;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\Staff\AccountUserUpdateForStaffQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class AccountUserUpdateForStaffAction
{
    public function __construct(
        private readonly AccountUserUpdateForStaffQuery $query,
    ) {}

    public function execute(AccountUser $user, array $payload): array
    {
        Gate::authorize('update', $user);

        Log::info('일반회원 정보 수정 실행', ['user_id' => $user->id]);

        $updated = $this->query->update($user, $payload)->fresh();

        return [
            'user' => AccountUserForStaffDetailDto::fromModel($updated)->toArray(),
        ];
    }
}
