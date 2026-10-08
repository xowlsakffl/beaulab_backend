<?php

namespace App\Domains\AccountStaff\Actions\Staff\Auth;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountStaff\Queries\Staff\Auth\PasswordUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * 스태프 비밀번호 변경 유스케이스.
 * 현재 비밀번호를 검증한 뒤 저장과 기존 토큰 만료를 Query에 위임한다.
 */
final class PasswordUpdateForStaffAction
{
    public function __construct(
        private readonly PasswordUpdateForStaffQuery $query,
    ) {}

    /**
     * @param  array{current_password:string,password:string}  $filters
     * @return array{message:string}
     */
    public function execute(AccountStaff $staff, array $filters): array
    {
        Gate::authorize('updateProfile', $staff);

        DB::transaction(function () use ($staff, $filters): void {
            $current = AccountStaff::query()->whereKey($staff->id)->lockForUpdate()->firstOrFail();
            abort_unless($current->isActive(), 403);
            Gate::forUser($current)->authorize('updateProfile', $current);
            if (! Hash::check($filters['current_password'], $current->password)) {
                Log::warning('뷰랩 직원 비밀번호 변경 실패', [
                    'reason' => 'current_password_mismatch',
                    'staff_id' => $staff->id,
                ]);

                throw ValidationException::withMessages(['current_password' => '현재 비밀번호가 올바르지 않습니다.']);
            }

            $this->query->update($current, (string) $filters['password']);
        });

        Log::info('뷰랩 직원 비밀번호 변경', [
            'staff_id' => $staff->id,
        ]);

        return [
            'message' => '비밀번호가 변경되었습니다. 다시 로그인해 주세요.',
        ];
    }
}
