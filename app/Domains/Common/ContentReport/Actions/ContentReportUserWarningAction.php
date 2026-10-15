<?php

declare(strict_types=1);

namespace App\Domains\Common\ContentReport\Actions;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Support\Facades\DB;

final class ContentReportUserWarningAction
{
    public function execute(int $authorId, bool $addWarning): array
    {
        return DB::transaction(function () use ($authorId, $addWarning): array {
            $author = AccountUser::query()->whereKey($authorId)->lockForUpdate()->first();
            if ($author === null) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '작성자 정보를 찾을 수 없습니다.');
            }

            $beforeCount = (int) $author->warning_count;
            $beforeStatus = (string) $author->status;
            $author->warning_count = max(0, $beforeCount + ($addWarning ? 1 : -1));
            $statusChanged = false;

            if ($addWarning && $author->warning_count >= AccountUser::WARNING_BLOCK_THRESHOLD
                && $beforeStatus === AccountUser::STATUS_ACTIVE) {
                $author->status = AccountUser::STATUS_BLOCKED;
                $author->blocked_at = now();
                $author->blocked_by_warning = true;
                $statusChanged = true;
            } elseif (! $addWarning && $beforeStatus === AccountUser::STATUS_BLOCKED
                && $author->blocked_by_warning
                && $author->warning_count < AccountUser::WARNING_BLOCK_THRESHOLD) {
                $author->status = AccountUser::STATUS_ACTIVE;
                $author->blocked_at = null;
                $author->blocked_by_warning = false;
                $statusChanged = true;
            }

            $author->save();

            return [
                'account_user_id' => (int) $author->id,
                'warning_count_before' => $beforeCount,
                'warning_count_after' => (int) $author->warning_count,
                'account_status_before' => $beforeStatus,
                'account_status_after' => (string) $author->status,
                $addWarning ? 'account_blocked' : 'account_unblocked' => $statusChanged,
            ];
        });
    }
}
