<?php

namespace App\Domains\AccountStaff\Queries\Staff\Auth;

use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * 스태프 프로필 저장 Query.
 * 본인 이름·전화번호 변경을 버전 검사 후 저장한다.
 */
final class ProfileUpdateForStaffQuery
{
    public function update(AccountStaff $staff, array $filters): AccountStaff
    {
        return DB::transaction(function () use ($staff, $filters): AccountStaff {
            $current = AccountStaff::query()->whereKey($staff->id)->lockForUpdate()->firstOrFail();
            abort_unless($current->isActive(), 403);
            Gate::forUser($current)->authorize('updateProfile', $current);
            if ((int) $current->lock_version !== (int) $filters['version']) {
                throw ValidationException::withMessages(['version' => '다른 화면에서 내 정보가 변경되었습니다. 최신 정보를 불러온 후 다시 수정해 주세요.']);
            }
            $current->fill(Arr::only($filters, ['name', 'phone']));
            if ($current->isDirty()) {
                $current->forceFill(['lock_version' => (int) $current->lock_version + 1])->save();
            }

            return $current;
        });
    }
}
