<?php

declare(strict_types=1);

namespace App\Domains\User\Actions\Staff;

use App\Domains\User\Models\AccountUser;
use App\Domains\User\Queries\Staff\AccountUserDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class AccountUserDeleteForStaffAction
{
    public function __construct(
        private readonly AccountUserDeleteForStaffQuery $query,
    ) {}

    public function execute(AccountUser $user): array
    {
        Gate::authorize('delete', $user);

        Log::info('일반회원 삭제(soft delete) 실행', ['user_id' => $user->id]);

        return DB::transaction(function () use ($user) {
            $this->query->softDelete($user);
            $user->refresh();

            return [
                'deleted_id' => (int) $user->id,
                'deleted_at' => optional($user->deleted_at)?->toISOString(),
            ];
        });
    }
}
