<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Actions\Staff;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\Staff\AccountUserDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * AccountUserDeleteForStaffAction 역할 정의.
 * 일반 회원 계정 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
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
