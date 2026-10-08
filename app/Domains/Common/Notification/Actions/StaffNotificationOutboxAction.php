<?php

namespace App\Domains\Common\Notification\Actions;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Notification\Jobs\ProcessStaffNotificationOutboxJob;
use App\Domains\Common\Notification\Models\NotificationInbox;
use App\Domains\Common\Notification\Models\NotificationPreference;
use App\Domains\Common\Notification\Support\StaffNotificationState;
use App\Domains\Common\Notification\Support\StaffNotificationTypeRegistry;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class StaffNotificationOutboxAction
{
    public function __construct(private readonly StaffNotificationTypeRegistry $registry) {}

    public function enqueue(int $outboxId): void
    {
        try {
            $claimed = DB::table('staff_notification_outbox')->where('id', $outboxId)->whereNull('processed_at')
                ->where(fn ($query) => $query->whereNull('next_dispatch_at')->orWhere('next_dispatch_at', '<=', now()))
                ->update(['next_dispatch_at' => now()->addMinutes(2)]);
            if ($claimed) {
                ProcessStaffNotificationOutboxJob::dispatch($outboxId);
            }
        } catch (\Throwable $exception) {
            Log::warning('직원 알림 outbox 큐 등록 실패. 예약 복구 대기.', ['outbox_id' => $outboxId, 'exception' => $exception::class]);
        }
    }

    public function process(int $outboxId): void
    {
        $outbox = DB::table('staff_notification_outbox')->where('id', $outboxId)->whereNull('processed_at')->first();
        if (! $outbox) {
            return;
        }
        DB::table('staff_notification_outbox')->where('id', $outboxId)->increment('attempts');
        try {
            foreach (json_decode($outbox->recipient_ids, true, 512, JSON_THROW_ON_ERROR) as $staffId) {
                $this->deliver($outbox, (int) $staffId);
            }
            DB::table('staff_notification_outbox')->where('id', $outboxId)->update(['processed_at' => now(), 'last_error' => null, 'updated_at' => now()]);
        } catch (\Throwable $exception) {
            DB::table('staff_notification_outbox')->where('id', $outboxId)->update(['last_error' => $exception::class, 'next_dispatch_at' => now()->addMinutes(2), 'updated_at' => now()]);
            throw $exception;
        }
    }

    public function recover(int $limit): array
    {
        $outboxIds = DB::table('staff_notification_outbox')->whereNull('processed_at')
            ->where(fn ($query) => $query->whereNull('next_dispatch_at')->orWhere('next_dispatch_at', '<=', now()))
            ->orderBy('id')->limit($limit)->pluck('id');
        foreach ($outboxIds as $id) {
            $this->enqueue((int) $id);
        }
        $staffIds = DB::table('staff_notification_states')->whereColumn('version', '>', 'broadcast_version')
            ->where(fn ($query) => $query->whereNull('next_dispatch_at')->orWhere('next_dispatch_at', '<=', now()))
            ->orderBy('staff_id')->limit($limit)->pluck('staff_id');
        foreach ($staffIds as $id) {
            StaffNotificationState::enqueue((int) $id);
        }

        return ['outbox' => $outboxIds->count(), 'invalidations' => $staffIds->count()];
    }

    private function deliver(object $outbox, int $staffId): void
    {
        DB::transaction(function () use ($outbox, $staffId): void {
            // 직원 단위 잠금으로 집계·읽음·스냅샷의 순서를 고정한다.
            StaffNotificationState::lock($staffId);
            if (DB::table('staff_notification_receipts')->where('outbox_id', $outbox->id)->where('staff_id', $staffId)->exists()) {
                return;
            }
            $receiptId = DB::table('staff_notification_receipts')->insertGetId([
                'outbox_id' => $outbox->id, 'staff_id' => $staffId, 'status' => 'SKIPPED', 'created_at' => now(),
            ]);
            $staff = AccountStaff::query()->find($staffId);
            if (! $staff || ! $this->registry->canAccess($staff) || (int) $outbox->actor_staff_id === $staffId) {
                return;
            }
            if ($outbox->event_type === StaffNotificationTypeRegistry::INVALIDATED) {
                StaffNotificationState::changed($staffId);
                DB::table('staff_notification_receipts')->where('id', $receiptId)->update(['status' => 'INVALIDATED']);

                return;
            }
            if (! $this->registry->allows($staff, $outbox->event_type)) {
                return;
            }
            $target = $this->registry->target($outbox->event_type, (int) $outbox->target_id);
            $isMessage = $outbox->event_type === StaffNotificationTypeRegistry::MESSAGE;
            if (! $target || ($isMessage && (int) $target->assigned_staff_id !== $staffId)) {
                return;
            }
            $preference = NotificationPreference::query()->where('owner_type', NotificationInbox::RECIPIENT_STAFF)
                ->where('owner_id', $staffId)->where('event_type', $outbox->event_type)->first();
            if (! ($preference?->in_app ?? $this->registry->defaultEnabled($outbox->event_type))) {
                return;
            }
            $payload = json_decode($outbox->payload, true, 512, JSON_THROW_ON_ERROR);
            $messageId = $isMessage ? (int) $payload['message_id'] : null;
            if ($isMessage) {
                $message = HospitalChatBotConsultationMessage::query()->whereKey($messageId)->where('consultation_id', $outbox->target_id)->first(['id', 'sequence']);
                if (! $message) {
                    return;
                }
                $readThrough = (int) DB::table('hospital_chatbot_consultation_reads')->where('staff_id', $staffId)->where('consultation_id', $outbox->target_id)->value('last_read_sequence');
                $bellRead = NotificationInbox::query()->where('recipient_type', NotificationInbox::RECIPIENT_STAFF)
                    ->where('recipient_id', $staffId)->where('event_type', StaffNotificationTypeRegistry::MESSAGE)
                    ->where('target_type', $outbox->target_type)->where('target_id', $outbox->target_id)
                    ->whereNotNull('read_at')->where('payload->message_id', '>=', $messageId)->exists();
                if ((int) $message->sequence <= $readThrough || $bellRead) {
                    DB::table('staff_notification_receipts')->where('id', $receiptId)->update(['status' => 'READ', 'message_id' => $messageId]);

                    return;
                }
            }

            $group = $isMessage ? 'staff:consultation:'.$outbox->target_id : null;
            $inbox = $group ? NotificationInbox::query()->where('recipient_type', NotificationInbox::RECIPIENT_STAFF)
                ->where('recipient_id', $staffId)->where('open_aggregation_key', $group)->lockForUpdate()->first() : null;
            $payload['last_receipt_id'] = $receiptId;
            if ($inbox) {
                // 큐 역전 시 최신 메시지와 표시 메타데이터는 뒤로 가지 않는다.
                if ((int) ($inbox->payload['message_id'] ?? 0) > $messageId) {
                    $payload = $inbox->payload;
                    $payload['last_receipt_id'] = $receiptId;
                }
                $inbox->forceFill(['event_count' => (int) $inbox->event_count + 1, 'payload' => $payload])->save();
            } else {
                $inbox = NotificationInbox::query()->create([
                    'recipient_type' => NotificationInbox::RECIPIENT_STAFF, 'recipient_id' => $staffId,
                    'actor_type' => $outbox->actor_staff_id ? NotificationInbox::ACTOR_STAFF : null, 'actor_id' => $outbox->actor_staff_id,
                    'event_type' => $outbox->event_type, 'title' => $this->registry->definition($outbox->event_type)['title'],
                    'body' => null, 'event_count' => 1, 'aggregation_key' => $group, 'open_aggregation_key' => $group,
                    'target_type' => $outbox->target_type, 'target_id' => $outbox->target_id, 'payload' => $payload,
                ]);
            }
            DB::table('staff_notification_receipts')->where('id', $receiptId)->update([
                'status' => 'DELIVERED', 'notification_inbox_id' => $inbox->id, 'message_id' => $messageId,
            ]);
            StaffNotificationState::changed($staffId);
        }, 3);
    }
}
