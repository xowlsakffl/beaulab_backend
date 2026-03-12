<?php

namespace App\Domains\Notice\Actions\Common;

use App\Domains\Notice\Jobs\SendNoticePushJob;
use App\Domains\Notice\Models\Notice;

final class DispatchNoticePushAction
{
    public function execute(Notice $notice): void
    {
        if (! (bool) $notice->is_push_enabled) {
            return;
        }

        if ($notice->push_sent_at !== null) {
            return;
        }

        if (! (bool) $notice->is_visible) {
            return;
        }

        $dispatchAt = $notice->publish_start_at;

        if ($dispatchAt !== null && $dispatchAt->isFuture()) {
            SendNoticePushJob::dispatch((int) $notice->id)
                ->onConnection('redis')
                ->onQueue('push')
                ->delay($dispatchAt);

            return;
        }

        SendNoticePushJob::dispatch((int) $notice->id)
            ->onConnection('redis')
            ->onQueue('push');
    }
}
