<?php

namespace App\Domains\Common\Notification\Actions;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Notification\Models\NotificationInbox;
use App\Domains\Common\Notification\Models\NotificationPreference;
use App\Domains\Common\Notification\Support\StaffNotificationState;
use App\Domains\Common\Notification\Support\StaffNotificationTypeRegistry;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StaffNotificationPublisher
{
    public function __construct(private readonly StaffNotificationTypeRegistry $registry) {}

    public function record(string $eventType, string $eventKey, string $targetType, int $targetId, array $payload = [], ?int $recipientId = null, ?int $actorStaffId = null): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('직원 알림은 업무 트랜잭션 안에서 기록해야 합니다.');
        }
        $definition = $this->registry->definition($eventType);
        if ($definition['target_type'] !== $targetType || $targetId < 1 || trim($eventKey) === '' || strlen($eventKey) > 191 || ($recipientId !== null && $recipientId < 1) || ($actorStaffId !== null && $actorStaffId < 1)) {
            throw new \InvalidArgumentException('직원 알림 대상 또는 이벤트 키가 올바르지 않습니다.');
        }
        $payload = $this->metadata($payload);
        if ($eventType === StaffNotificationTypeRegistry::MESSAGE && (! isset($payload['message_id']) || $recipientId === null)) {
            throw new \InvalidArgumentException('상담 메시지 알림에는 메시지 ID와 담당 직원 ID가 필요합니다.');
        }
        $hash = hash('sha256', json_encode([$eventType, $targetType, $targetId, $payload, $recipientId, $actorStaffId], JSON_THROW_ON_ERROR));
        $existing = DB::table('staff_notification_outbox')->where('event_key', $eventKey)->first();
        if ($existing) {
            $this->assertSameEvent($existing, $hash);

            return;
        }

        // 업무 발생 당시 수신자를 고정한다. 전달 시 권한은 다시 검사하되 새 직원을 추가하지 않는다.
        $recipients = [];
        $target = $this->registry->target($eventType, $targetId);
        $preferences = NotificationPreference::query()->where('owner_type', NotificationInbox::RECIPIENT_STAFF)
            ->where('event_type', $eventType)->pluck('in_app', 'owner_id');
        if ($target !== null) {
            foreach (AccountStaff::query()->with(['permissions', 'roles.permissions'])->where('status', AccountStaff::STATUS_ACTIVE)->when($recipientId !== null, fn ($query) => $query->whereKey($recipientId))->lazyById(100) as $staff) {
                if ((int) $staff->id !== $actorStaffId && $this->registry->allows($staff, $eventType)
                    && ($preferences->get($staff->id) ?? $this->registry->defaultEnabled($eventType))
                    && ($eventType !== StaffNotificationTypeRegistry::MESSAGE || (int) $target->assigned_staff_id === (int) $staff->id)) {
                    $recipients[] = (int) $staff->id;
                }
            }
        }
        DB::table('staff_notification_outbox')->insertOrIgnore([
            'event_key' => $eventKey, 'request_hash' => $hash, 'event_type' => $eventType,
            'target_type' => $targetType, 'target_id' => $targetId, 'actor_staff_id' => $actorStaffId,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'recipient_ids' => json_encode($recipients, JSON_THROW_ON_ERROR),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // 동시 중복 입력에서도 최초 원본만 유효하다.
        $outbox = DB::table('staff_notification_outbox')->where('event_key', $eventKey)->lockForUpdate()->first();
        $this->assertSameEvent($outbox, $hash);
        DB::afterCommit(fn () => app(StaffNotificationOutboxAction::class)->enqueue((int) $outbox->id));
    }

    public function invalidateTarget(string $targetType, int $targetId): void
    {
        if ($targetId < 1 || ! in_array($targetType, array_column($this->registry->definitions(), 'target_type'), true)) {
            throw new \InvalidArgumentException('지원하지 않는 직원 알림 대상입니다.');
        }
        DB::transaction(function () use ($targetType, $targetId): void {
            $recipients = NotificationInbox::query()->where('recipient_type', NotificationInbox::RECIPIENT_STAFF)
                ->where('target_type', $targetType)->where('target_id', $targetId)->pluck('recipient_id')->all();
            // 아직 알림함에 반영되지 않은 이벤트의 수신자도 상태 재조회 대상이다.
            foreach (DB::table('staff_notification_outbox')->where('target_type', $targetType)->where('target_id', $targetId)->whereNull('processed_at')->pluck('recipient_ids') as $ids) {
                $recipients = array_merge($recipients, json_decode($ids, true, 512, JSON_THROW_ON_ERROR));
            }
            $key = 'invalidate:'.Str::uuid();
            $id = DB::table('staff_notification_outbox')->insertGetId([
                'event_key' => $key, 'request_hash' => hash('sha256', $key),
                'event_type' => StaffNotificationTypeRegistry::INVALIDATED, 'target_type' => $targetType, 'target_id' => $targetId,
                'payload' => '{}', 'recipient_ids' => json_encode(array_values(array_unique(array_map('intval', $recipients))), JSON_THROW_ON_ERROR),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::afterCommit(fn () => app(StaffNotificationOutboxAction::class)->enqueue($id));
        });
    }

    public function markConversationRead(int $staffId, int $consultationId, int $messageId): void
    {
        DB::transaction(function () use ($staffId, $consultationId, $messageId): void {
            $staff = AccountStaff::query()->find($staffId);
            if (! $staff || ! $this->registry->canAccess($staff)
                || ! HospitalChatBotConsultation::query()->whereKey($consultationId)->where('assigned_staff_id', $staffId)->exists()) {
                return;
            }
            $message = HospitalChatBotConsultationMessage::query()->whereKey($messageId)->where('consultation_id', $consultationId)->first(['id', 'sequence']);
            if (! $message) {
                return;
            }
            StaffNotificationState::lock($staffId);
            $readSequence = (int) DB::table('hospital_chatbot_consultation_reads')->where('staff_id', $staffId)->where('consultation_id', $consultationId)->value('last_read_sequence');
            $throughSequence = min((int) $message->sequence, $readSequence);
            $readMessageIds = HospitalChatBotConsultationMessage::query()->select('id')->where('consultation_id', $consultationId)->where('sequence', '<=', $throughSequence);
            $changed = $this->registry->visibleQuery($staff)->where('event_type', StaffNotificationTypeRegistry::MESSAGE)
                ->where('target_id', $consultationId)->whereNull('read_at')
                ->whereIn('payload->message_id', $readMessageIds)
                ->update(['read_at' => now(), 'open_aggregation_key' => null, 'updated_at' => now()]);
            if ($changed > 0) {
                StaffNotificationState::changed($staffId);
            }
        }, 3);
    }

    private function assertSameEvent(object $outbox, string $hash): void
    {
        if (! hash_equals($outbox->request_hash, $hash)) {
            throw new \LogicException('같은 직원 알림 이벤트 키에 다른 내용을 기록할 수 없습니다.');
        }
    }

    private function metadata(array $payload): array
    {
        if (array_diff(array_keys($payload), ['hospital_id', 'hospital_name', 'name', 'target_name', 'message_id']) !== []) {
            throw new \InvalidArgumentException('직원 알림에는 허용된 식별자와 이름 메타데이터만 기록할 수 있습니다.');
        }
        if (isset($payload['name'])) {
            $payload['hospital_name'] ??= $payload['name'];
            unset($payload['name']);
        }
        foreach ($payload as $key => $value) {
            if (in_array($key, ['hospital_id', 'message_id'], true)) {
                if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
                    throw new \InvalidArgumentException('직원 알림 메타데이터 ID가 올바르지 않습니다.');
                }
                $payload[$key] = (int) $value;
            } elseif (! is_string($value) || mb_strlen($value) > 255) {
                throw new \InvalidArgumentException('직원 알림 이름은 255자 이하의 문자열이어야 합니다.');
            }
        }
        ksort($payload);

        return $payload;
    }
}
