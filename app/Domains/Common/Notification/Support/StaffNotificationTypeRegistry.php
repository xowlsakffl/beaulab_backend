<?php

namespace App\Domains\Common\Notification\Support;

use App\Common\Authorization\AccessPermissions as P;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Notification\Models\NotificationInbox;
use App\Domains\Hospital\Models\HospitalStatusChangeRequest;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\RemoteConsultation\Models\RemoteConsultation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StaffNotificationTypeRegistry
{
    public const MESSAGE = 'staff.hospital_chatbot.message';

    public const INVALIDATED = 'staff.target.invalidated';

    public function definitions(): array
    {
        return [
            'staff.hospital_event.requested' => ['title' => '이벤트 검수 신청', 'target_type' => 'hospital_event', 'model' => HospitalEvent::class, 'route' => '/ads-manage/events/%d', 'status' => 'allow_status', 'pending' => ['PENDING', 'REVIEWING'], 'permissions' => [P::BEAULAB_HOSPITAL_EVENT_SHOW, P::BEAULAB_HOSPITAL_EVENT_STATUS_UPDATE]],
            'staff.hospital_event_ad.requested' => ['title' => '광고 검수 신청', 'target_type' => 'hospital_event_ad', 'model' => HospitalEventAd::class, 'route' => '/ads-manage/event-ads/%d', 'status' => 'allow_status', 'pending' => ['PENDING', 'REVIEWING'], 'permissions' => [P::BEAULAB_HOSPITAL_EVENT_AD_SHOW, P::BEAULAB_HOSPITAL_EVENT_AD_STATUS_UPDATE]],
            'staff.hospital_chatbot.requested' => ['title' => '상담 연결 요청', 'target_type' => 'hospital_chatbot_consultation', 'model' => HospitalChatBotConsultation::class, 'route' => '/hospital-chatbot/consultations/%d', 'status' => 'status', 'pending' => ['WAITING'], 'permissions' => [P::BEAULAB_CHATBOT_CONSULTATION_SHOW, P::BEAULAB_CHATBOT_CONSULTATION_PROCESS]],
            self::MESSAGE => ['title' => '상담 새 메시지', 'target_type' => 'hospital_chatbot_consultation', 'model' => HospitalChatBotConsultation::class, 'route' => '/hospital-chatbot/consultations/%d', 'status' => 'status', 'pending' => ['CONNECTED'], 'permissions' => [P::BEAULAB_CHATBOT_CONSULTATION_SHOW, P::BEAULAB_CHATBOT_CONSULTATION_PROCESS]],
            'staff.hospital_event_db.reported' => ['title' => '이벤트 DB 미인증 신고', 'target_type' => 'hospital_event_db', 'model' => HospitalEventDB::class, 'route' => '/customer-db-manage/events?hospital_event_db_id=%d', 'status' => 'allow_status', 'pending' => ['UNVERIFIED_REPORTED'], 'permissions' => [P::BEAULAB_HOSPITAL_EVENT_DB_SHOW, P::BEAULAB_HOSPITAL_EVENT_DB_STATUS_UPDATE]],
            'staff.hospital_event_db.created' => ['title' => '이벤트 DB 신규 신청', 'target_type' => 'hospital_event_db', 'model' => HospitalEventDB::class, 'route' => '/customer-db-manage/events?hospital_event_db_id=%d', 'status' => 'status', 'pending' => ['NEW'], 'default_enabled' => false, 'permissions' => [P::BEAULAB_HOSPITAL_EVENT_DB_SHOW]],
            'staff.remote_consultation.requested' => ['title' => '비대면 상담 신청', 'target_type' => 'remote_consultation', 'model' => RemoteConsultation::class, 'route' => '/customer-db-manage/consultations/%d', 'status' => 'approval_status', 'pending' => ['PENDING', 'REVIEWING'], 'permissions' => [P::BEAULAB_REMOTE_CONSULTATION_SHOW, P::BEAULAB_REMOTE_CONSULTATION_APPROVE]],
            'staff.remote_consultation.assignment_failed' => ['title' => '비대면 상담 배정 확인 필요', 'target_type' => 'remote_consultation', 'model' => RemoteConsultation::class, 'route' => '/customer-db-manage/consultations/%d/quotes', 'status' => 'approval_status', 'pending' => [], 'permissions' => [P::BEAULAB_REMOTE_CONSULTATION_SHOW, P::BEAULAB_REMOTE_CONSULTATION_ASSIGN]],
            'staff.real_model.requested' => ['title' => '리얼모델 신청', 'target_type' => 'hospital_event_real_model_db', 'model' => HospitalEventRealModelDB::class, 'route' => '/customer-db-manage/real-models/%d', 'status' => 'status', 'pending' => ['RECEIVED'], 'permissions' => [P::BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_SHOW, P::BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_STATUS_UPDATE]],
            NotificationInbox::EVENT_HOSPITAL_STATUS_CHANGE_REQUESTED => ['title' => '병의원 운영중지 신청', 'target_type' => 'hospital_status_change_request', 'model' => HospitalStatusChangeRequest::class, 'route' => '/hospital-manage/hospitals/%d', 'status' => 'status', 'pending' => ['PENDING'], 'permissions' => [P::BEAULAB_HOSPITAL_SHOW, P::BEAULAB_HOSPITAL_STATUS_REQUEST_SHOW, P::BEAULAB_HOSPITAL_STATUS_REQUEST_PROCESS]],
            NotificationInbox::EVENT_HOSPITAL_STATUS_CHANGE_PROCESSED => ['title' => '병의원 운영상태 변경 신청 처리', 'target_type' => 'hospital_status_change_request', 'model' => HospitalStatusChangeRequest::class, 'route' => '/hospital-manage/hospitals/%d', 'status' => 'status', 'pending' => [], 'actionable' => false, 'permissions' => [P::BEAULAB_HOSPITAL_SHOW, P::BEAULAB_HOSPITAL_STATUS_REQUEST_SHOW]],
        ];
    }

    public function definition(string $eventType): array
    {
        return $this->definitions()[$eventType] ?? throw new \InvalidArgumentException('지원하지 않는 직원 알림 유형입니다.');
    }

    public function defaultEnabled(string $eventType): bool
    {
        return $this->definition($eventType)['default_enabled'] ?? true;
    }

    public function canAccess(AccountStaff $staff): bool
    {
        return $staff->isActive() && ! $staff->trashed();
    }

    public function allows(AccountStaff $staff, string $eventType): bool
    {
        $definition = $this->definitions()[$eventType] ?? null;
        if (! $definition || ! $this->canAccess($staff)) {
            return false;
        }
        foreach ($definition['permissions'] as $permission) {
            if (! $staff->can($permission)) {
                return false;
            }
        }

        return true;
    }

    public function allowedTypes(AccountStaff $staff): array
    {
        return array_values(array_filter(array_keys($this->definitions()), fn (string $type): bool => $this->allows($staff, $type)));
    }

    public function visibleQuery(AccountStaff $staff): Builder
    {
        return NotificationInbox::query()
            ->where('recipient_type', NotificationInbox::RECIPIENT_STAFF)
            ->where('recipient_id', $staff->id)
            ->where(function (Builder $query) use ($staff): void {
                $query->whereRaw('1 = 0');
                foreach ($this->allowedTypes($staff) as $type) {
                    $definition = $this->definition($type);
                    $query->orWhere(function (Builder $query) use ($type, $definition, $staff): void {
                        $query->where('event_type', $type)->where('target_type', $definition['target_type']);
                        if ($type === self::MESSAGE) {
                            $query->whereIn('target_id', HospitalChatBotConsultation::query()->select('id')->where('assigned_staff_id', $staff->id));
                        }
                    });
                }
            });
    }

    public function target(string $eventType, int $targetId): ?Model
    {
        return $this->definition($eventType)['model']::query()->find($targetId);
    }

    public function items(Collection $inboxes): array
    {
        if ($inboxes->isEmpty()) {
            return [];
        }

        // 표시 데이터는 현재 묶음 기준이다. 수신 시각도 페이지 정렬 스냅샷으로 제한하지 않는다.
        $receipts = DB::table('staff_notification_receipts')
            ->whereIn('notification_inbox_id', $inboxes->pluck('id'))
            ->groupBy('notification_inbox_id')
            ->selectRaw('notification_inbox_id, MAX(id) AS last_receipt_id, MAX(created_at) AS last_received_at')
            ->get()->keyBy('notification_inbox_id');

        $targets = [];
        foreach ($inboxes->groupBy('target_type') as $targetType => $group) {
            $definition = $this->definition($group->first()->event_type);
            $statusColumns = $group->pluck('event_type')->unique()->map(fn (string $type): string => $this->definition($type)['status'])->all();
            $columns = array_unique(['id', ...$statusColumns, ...($targetType === 'hospital_status_change_request' ? ['hospital_id'] : [])]);
            $targets[$targetType] = $definition['model']::query()->whereIn('id', $group->pluck('target_id'))->get($columns)->keyBy('id');
        }

        return $inboxes->map(fn (NotificationInbox $inbox): array => $this->item(
            $inbox,
            $targets[$inbox->target_type]->get($inbox->target_id),
            $receipts->get($inbox->id)?->last_received_at,
            (int) ($receipts->get($inbox->id)?->last_receipt_id ?? 0),
        ))->values()->all();
    }

    private function item(NotificationInbox $inbox, ?Model $target, ?string $lastReceivedAt, int $lastReceiptId): array
    {
        $definition = $this->definition($inbox->event_type);
        $status = $target?->getAttribute($definition['status']);
        $routeId = $inbox->target_type === 'hospital_status_change_request' ? $target?->hospital_id : $inbox->target_id;
        $payload = array_intersect_key($inbox->payload ?? [], array_flip(['hospital_id', 'hospital_name', 'target_name', 'message_id']));

        return [
            'id' => (int) $inbox->id,
            'event_type' => $inbox->event_type,
            'title' => $definition['title'],
            'event_count' => (int) $inbox->event_count,
            'target_type' => $inbox->target_type,
            'target_id' => (int) $inbox->target_id,
            'target_route' => $target ? sprintf($definition['route'], $routeId) : null,
            'target_available' => $target !== null,
            'target_status' => $status,
            'target_actionable' => $target !== null && ($definition['actionable'] ?? in_array($status, $definition['pending'], true)),
            'latest_message_id' => isset($payload['message_id']) ? (int) $payload['message_id'] : null,
            'payload' => $payload,
            'read_at' => $inbox->read_at?->toIso8601String(),
            'last_receipt_id' => $lastReceiptId,
            'last_received_at' => $lastReceivedAt !== null
                ? Carbon::parse($lastReceivedAt, config('app.timezone'))->toIso8601String()
                : $inbox->created_at?->toIso8601String(),
            'created_at' => $inbox->created_at?->toIso8601String(),
            'updated_at' => $inbox->updated_at?->toIso8601String(),
        ];
    }
}
