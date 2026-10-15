<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\ContentReport\Support\ContentReportNotification;
use App\Domains\Common\Notification\Models\NotificationInbox;
use App\Domains\Common\Notification\Support\StaffNotificationTypeRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class StaffNotificationDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        DB::transaction(function (): void {
            $registry = app(StaffNotificationTypeRegistry::class);
            $staffs = AccountStaff::where('status', 'ACTIVE')->with(['permissions', 'roles.permissions'])->get();
            foreach ($registry->definitions() as $type => $definition) {
                if (in_array($type, config('staff_notifications.hidden_types', []), true) || ! $registry->defaultEnabled($type)) {
                    continue;
                }
                $query = isset($definition['report_alias']) ? ContentReportNotification::targets($definition) : $definition['model']::query();
                if ($definition['pending']) {
                    $query->whereIn($definition['status'], $definition['pending']);
                }
                foreach ($query->orderBy('id')->take(3)->get() as $index => $target) {
                    $key = 'development:'.$type.':'.$target->id;
                    if (DB::table('staff_notification_outbox')->where('event_key', $key)->exists()) {
                        continue;
                    }
                    $recipients = $staffs->filter(fn ($staff) => $registry->allows($staff, $type)
                        && ($type !== $registry::MESSAGE || $target->assigned_staff_id === $staff->id));
                    $payload = ['target_name' => $target->getAttribute('name') ?? $target->getAttribute('hospital_name_snapshot') ?? '개발용 알림'];
                    if ($type === $registry::MESSAGE) {
                        $message = $target->messages()->where('sender_type', 'HOSPITAL_USER')->latest('sequence')->first();
                        if (! $message) {
                            continue;
                        }
                        $payload['message_id'] = $message->id;
                    }
                    if ($index === 2) {
                        $payload['resolved_at'] = now()->toIso8601String();
                    }
                    $at = now()->subMinutes(5 + $index * 10);
                    // 큐/방송을 실행하지 않고 전달 완료된 개발용 알림과 영수증을 함께 만든다.
                    $outbox = DB::table('staff_notification_outbox')->insertGetId([
                        'event_key' => $key, 'request_hash' => hash('sha256', $key), 'event_type' => $type,
                        'target_type' => $definition['target_type'], 'target_id' => $target->id,
                        'payload' => json_encode($payload), 'recipient_ids' => json_encode($recipients->modelKeys()),
                        'processed_at' => $at, 'created_at' => $at, 'updated_at' => $at,
                    ]);
                    foreach ($recipients as $staff) {
                        $inbox = NotificationInbox::create([
                            'recipient_type' => 'STAFF', 'recipient_id' => $staff->id,
                            'event_type' => $type, 'title' => $definition['title'],
                            'target_type' => $definition['target_type'], 'target_id' => $target->id,
                            'payload' => $payload, 'event_count' => 1,
                            'read_at' => $index === 1 ? $at->copy()->addMinute() : null,
                            'created_at' => $at, 'updated_at' => $at,
                        ]);
                        $receipt = DB::table('staff_notification_receipts')->insertGetId([
                            'outbox_id' => $outbox, 'staff_id' => $staff->id, 'notification_inbox_id' => $inbox->id,
                            'message_id' => $payload['message_id'] ?? null, 'status' => 'DELIVERED', 'created_at' => $at,
                        ]);
                        $inbox->update(['payload' => [...$payload, 'last_receipt_id' => $receipt]]);
                        DB::table('staff_notification_states')->insertOrIgnore(['staff_id' => $staff->id, 'version' => 1, 'broadcast_version' => 1, 'created_at' => now(), 'updated_at' => now()]);
                    }
                }
            }
        });
    }
}
