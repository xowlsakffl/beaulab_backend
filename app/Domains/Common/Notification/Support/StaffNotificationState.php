<?php

namespace App\Domains\Common\Notification\Support;

use App\Domains\Common\Notification\Jobs\BroadcastStaffNotificationsJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class StaffNotificationState
{
    public static function lock(int $staffId): object
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('직원 알림 상태 잠금은 트랜잭션 안에서 사용해야 합니다.');
        }
        DB::table('staff_notification_states')->insertOrIgnore([
            'staff_id' => $staffId, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('staff_notification_states')->where('staff_id', $staffId)->lockForUpdate()->first();
    }

    public static function changed(int $staffId): void
    {
        self::lock($staffId);
        DB::table('staff_notification_states')->where('staff_id', $staffId)->update([
            'version' => DB::raw('version + 1'), 'updated_at' => now(),
        ]);
        DB::afterCommit(fn () => self::enqueue($staffId));
    }

    public static function enqueue(int $staffId): void
    {
        try {
            $claimed = DB::table('staff_notification_states')->where('staff_id', $staffId)
                ->whereColumn('version', '>', 'broadcast_version')
                ->where(fn ($query) => $query->whereNull('next_dispatch_at')->orWhere('next_dispatch_at', '<=', now()))
                ->update(['next_dispatch_at' => now()->addMinutes(2)]);
            if ($claimed) {
                BroadcastStaffNotificationsJob::dispatch($staffId);
            }
        } catch (\Throwable $exception) {
            Log::warning('직원 알림 갱신 큐 등록 실패. 예약 복구 대기.', ['staff_id' => $staffId, 'exception' => $exception::class]);
        }
    }
}
