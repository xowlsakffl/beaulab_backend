<?php

namespace App\Console\Commands;

use App\Domains\Common\Notification\Actions\StaffNotificationOutboxAction;
use Illuminate\Console\Command;

final class DispatchStaffNotifications extends Command
{
    protected $signature = 'staff-notifications:dispatch-pending {--limit=100}';

    protected $description = '직원 인앱 알림 outbox와 누락된 갱신 방송을 전용 큐에 등록합니다.';

    public function handle(StaffNotificationOutboxAction $action): int
    {
        $counts = $action->recover(max(1, min(1000, (int) $this->option('limit'))));
        $this->info('직원 알림 복구 등록: outbox '.$counts['outbox'].', 갱신 '.$counts['invalidations']);

        return self::SUCCESS;
    }
}
