<?php

namespace App\Domains\Notice\Jobs;

use App\Domains\Notice\Models\Notice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class SendNoticePushJob implements ShouldQueue
{
    use Queueable;

    public string $connection = 'redis';
    public string $queue = 'push';
    public int $tries = 3;

    public function __construct(
        public int $noticeId,
    ) {}

    public function handle(): void
    {
        $notice = Notice::query()->find($this->noticeId);
        if (! $notice) {
            return;
        }

        if (! (bool) $notice->is_push_enabled) {
            return;
        }

        if ($notice->push_sent_at !== null) {
            return;
        }

        if (! (bool) $notice->is_visible) {
            return;
        }

        $now = now();

        if ($notice->publish_start_at !== null && $notice->publish_start_at->gt($now)) {
            self::dispatch((int) $notice->id)
                ->onConnection('redis')
                ->onQueue('push')
                ->delay($notice->publish_start_at);

            return;
        }

        if (
            ! (bool) $notice->is_publish_period_unlimited
            && $notice->publish_end_at !== null
            && $notice->publish_end_at->lt($now)
        ) {
            return;
        }

        // Placeholder hook point for FCM/APNS integration.
        Log::info('Notice push dispatch placeholder', [
            'notice_id' => (int) $notice->id,
            'channel' => (string) $notice->channel,
            'title' => (string) $notice->title,
            'publish_start_at' => $notice->publish_start_at?->toISOString(),
        ]);

        $notice->forceFill([
            'push_sent_at' => $now,
        ])->save();
    }
}
