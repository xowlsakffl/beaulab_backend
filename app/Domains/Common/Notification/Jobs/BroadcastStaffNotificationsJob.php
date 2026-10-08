<?php

namespace App\Domains\Common\Notification\Jobs;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Notification\Support\StaffNotificationState;
use App\Domains\Common\Notification\Support\StaffNotificationTypeRegistry;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;

final class BroadcastStaffNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public readonly int $staffId)
    {
        $this->onConnection('redis')->onQueue('staff-notifications');
    }

    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(StaffNotificationTypeRegistry $registry): void
    {
        $state = DB::table('staff_notification_states')->where('staff_id', $this->staffId)->first();
        if (! $state || (int) $state->version <= (int) $state->broadcast_version) {
            return;
        }
        $staff = AccountStaff::query()->find($this->staffId);
        if ($staff && $registry->canAccess($staff)) {
            if (in_array(config('broadcasting.default'), ['log', 'null', null], true)) {
                return;
            }
            // 계정 ID는 채널 경로에만 사용한다. 본문·집계·대상·이름은 전송하지 않는다.
            Broadcast::connection()->broadcast([new PrivateChannel('staff.'.$this->staffId)], 'staff.notifications.invalidate', []);
        }
        DB::transaction(function () use ($state): void {
            $current = StaffNotificationState::lock($this->staffId);
            DB::table('staff_notification_states')->where('staff_id', $this->staffId)->update([
                'broadcast_version' => max((int) $current->broadcast_version, (int) $state->version),
                'next_dispatch_at' => null,
            ]);
            if ((int) $current->version > (int) $state->version) {
                DB::afterCommit(fn () => StaffNotificationState::enqueue($this->staffId));
            }
        });
    }
}
