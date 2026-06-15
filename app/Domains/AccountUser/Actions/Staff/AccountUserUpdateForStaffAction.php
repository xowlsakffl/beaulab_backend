<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Actions\Staff;

use App\Domains\AccountUser\Dto\Staff\AccountUserForStaffDetailDto;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\Staff\AccountUserUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * AccountUserUpdateForStaffAction 역할 정의.
 * 일반 회원 계정 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class AccountUserUpdateForStaffAction
{
    public function __construct(
        private readonly AccountUserUpdateForStaffQuery $query,
        private readonly AccountUserUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(AccountUser $user, array $payload): array
    {
        Gate::authorize('update', $user);

        Log::info('일반회원 정보 수정 실행', ['user_id' => $user->id]);

        $updated = DB::transaction(function () use ($user, $payload) {
            $before = $this->historyRecordAction->capture($user);
            $updated = $this->query->update($user, $payload)->fresh();
            $this->historyRecordAction->recordUpdated($updated, $before);

            return $updated;
        });

        return [
            'user' => AccountUserForStaffDetailDto::fromModel($updated)->toArray(),
        ];
    }
}
