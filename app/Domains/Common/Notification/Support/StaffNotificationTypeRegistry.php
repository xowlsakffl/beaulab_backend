<?php

namespace App\Domains\Common\Notification\Support;

use App\Common\Authorization\AccessPermissions as P;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\ContentReport\Support\ContentReportNotification;
use App\Domains\Common\ContentReport\Support\ContentReportTargetRegistry;
use App\Domains\Common\Notification\Models\NotificationInbox;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalStatusChangeRequest;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalEntry\Models\HospitalEntry;
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
            ...ContentReportNotification::definitions(),
            'staff.hospital.requested' => ['title' => '병의원 검수 신청', 'target_type' => 'hospital', 'model' => Hospital::class, 'route' => '/hospital-manage/hospitals/%d', 'status' => 'allow_status', 'pending' => ['PENDING', 'REVIEWING'], 'permissions' => [P::BEAULAB_HOSPITAL_SHOW, P::BEAULAB_HOSPITAL_STATUS_UPDATE]],
            'staff.doctor.requested' => ['title' => '의료진 검수 신청', 'target_type' => 'doctor', 'model' => HospitalDoctor::class, 'route' => '/hospital-manage/doctors/%d', 'status' => 'allow_status', 'pending' => ['PENDING', 'REVIEWING'], 'permissions' => [P::BEAULAB_DOCTOR_SHOW, P::BEAULAB_DOCTOR_STATUS_UPDATE]],
            'staff.hospital_entry.requested' => ['title' => '병의원 입점 신청', 'target_type' => 'hospital_entry', 'model' => HospitalEntry::class, 'route' => '/hospital-manage/hospital-entries/%d', 'status' => 'allow_status', 'pending' => ['PENDING', 'REVIEWING'], 'permissions' => [P::BEAULAB_HOSPITAL_ENTRY_SHOW, P::BEAULAB_HOSPITAL_ENTRY_STATUS_UPDATE]],
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

    public function target(string $eventType, int $targetId, bool $lock = false): ?Model
    {
        $definition = $this->definition($eventType);

        return $this->targets($definition)->when($lock, fn (Builder $query) => $query->lockForUpdate())->find($targetId);
    }

    private function targets(array $definition): Builder
    {
        $query = isset($definition['report_alias']) ? ContentReportNotification::targets($definition) : $definition['model']::query();
        if ($definition['target_type'] === 'hospital_status_change_request') {
            $query->whereHas('hospital');
        }

        return $query;
    }

    public function unreadQuery(AccountStaff $staff): Builder
    {
        return $this->visibleQuery($staff)->whereNull('read_at')->whereNull('payload->resolved_at')->where(function (Builder $query) use ($staff): void {
            $query->whereRaw('1 = 0');
            foreach ($this->allowedTypes($staff) as $type) {
                $definition = $this->definition($type);
                $targets = $this->targets($definition)->select('id');
                if ($definition['pending'] !== []) {
                    $targets->whereIn($definition['status'], $definition['pending']);
                }
                $query->orWhere(fn (Builder $query) => $query->where('event_type', $type)->whereIn('target_id', $targets));
            }
        });
    }

    public function menuPath(string $eventType): string
    {
        $definition = $this->definition($eventType);
        if (isset($definition['menu'])) {
            return $definition['menu'];
        }
        $route = $definition['route'];

        return preg_replace('~(?:/%d.*|\?.*)$~', '', $route);
    }

    public function badgeCounts(AccountStaff $staff): array
    {
        $badges = [];
        foreach ($this->allowedTypes($staff) as $type) {
            $badges[$this->menuPath($type)] = ['count' => 0, 'has_new' => false];
        }
        foreach ($this->unreadQuery($staff)->select('event_type')->selectRaw('COUNT(*) AS total')->groupBy('event_type')->get() as $row) {
            $path = $this->menuPath($row->event_type);
            $count = ($badges[$path]['count'] ?? 0) + (int) $row->total;
            $badges[$path] = ['count' => $count, 'has_new' => $count > 0];
        }

        return $badges;
    }

    public function constrainTarget(Builder $query, string $type, int $id): void
    {
        if (str_starts_with($type, 'report:')) {
            $alias = substr($type, 7);
            $types = array_keys(array_filter(ContentReportNotification::definitions(), fn (array $definition): bool => $definition['report_alias'] === $alias));
            if ($types === []) {
                $query->whereRaw('1 = 0');

                return;
            }
            $query->whereIn('event_type', $types)->whereIn('target_id', ContentReportState::query()->select('id')
                ->where('target_type', \App\Domains\Common\ContentReport\Support\ContentReportTargetRegistry::classForAlias($alias))->where('target_id', $id));

            return;
        }
        if ($type === 'hospital_chatbot_consultation') {
            $query->where('event_type', '!=', self::MESSAGE);
        }
        $query->where(function (Builder $query) use ($type, $id): void {
            $query->where(fn (Builder $query) => $query->where('target_type', $type)->where('target_id', $id));
            if ($type === 'hospital') {
                $query->orWhere(fn (Builder $query) => $query->where('target_type', 'hospital_status_change_request')
                    ->whereIn('target_id', HospitalStatusChangeRequest::query()->select('id')->where('hospital_id', $id)));
            }
        });
    }

    public function unreadTargetCounts(AccountStaff $staff, array $targets): array
    {
        $groups = collect($targets)->groupBy('target_type')
            ->map(fn (Collection $rows): array => $rows->pluck('target_id')->map(fn ($id): int => (int) $id)->unique()->all());
        $definitions = array_intersect_key($this->definitions(), array_flip($this->allowedTypes($staff)));
        $allowedTargets = array_column($definitions, 'target_type');
        $mapped = [];
        foreach ($groups as $type => $ids) {
            if (in_array($type, $allowedTargets, true)) {
                foreach ($ids as $id) {
                    $mapped[$type][$id][$type] = $id;
                }
            }
        }

        // 원본 행과 수신함 대상의 대응을 유형별로 한 번만 조회한다.
        if ($groups->has('hospital') && in_array('hospital_status_change_request', $allowedTargets, true)) {
            foreach (HospitalStatusChangeRequest::query()->whereIn('hospital_id', $groups['hospital'])->get(['id', 'hospital_id']) as $request) {
                $mapped['hospital_status_change_request'][$request->id]['hospital'] = (int) $request->hospital_id;
            }
        }
        $reports = collect($definitions)->filter(fn (array $definition): bool => isset($definition['report_alias']))->groupBy('report_alias');
        foreach ($reports as $alias => $reportDefinitions) {
            $type = 'report:'.$alias;
            if (! $groups->has($type)) {
                continue;
            }
            $states = ContentReportState::query()->where('target_type', ContentReportTargetRegistry::classForAlias($alias))
                ->whereIn('target_id', $groups[$type])->get(['id', 'target_id']);
            foreach ($states as $state) {
                foreach ($reportDefinitions as $definition) {
                    $mapped[$definition['target_type']][$state->id][$type] = (int) $state->target_id;
                }
            }
        }

        $counts = [];
        if ($mapped !== []) {
            // 읽음 스냅샷의 constrainTarget과 달리 담당 상담의 새 메시지도 센다.
            $rows = $this->unreadQuery($staff)->where(function (Builder $query) use ($mapped): void {
                foreach ($mapped as $type => $ids) {
                    $query->orWhere(fn (Builder $query) => $query->where('target_type', $type)->whereIn('target_id', array_keys($ids)));
                }
            })->select('target_type', 'target_id')->selectRaw('COUNT(*) AS total')->groupBy('target_type', 'target_id')->get();
            foreach ($rows as $row) {
                foreach ($mapped[$row->target_type][$row->target_id] as $type => $id) {
                    $counts[$type][$id] = ($counts[$type][$id] ?? 0) + (int) $row->total;
                }
            }
        }

        return array_map(fn (array $target): array => [
            'target_type' => $target['target_type'],
            'target_id' => (int) $target['target_id'],
            'unread_count' => $counts[$target['target_type']][(int) $target['target_id']] ?? 0,
        ], $targets);
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
            $columns = array_unique(['id', ...$statusColumns, ...($targetType === 'hospital_status_change_request' ? ['hospital_id'] : []), ...(isset($definition['report_alias']) ? ['target_id'] : [])]);
            $targets[$targetType] = $this->targets($definition)->whereIn('id', $group->pluck('target_id'))->get($columns)->keyBy('id');
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
        if (isset($definition['report_alias'])) {
            $routeId = $target?->target_id;
        }
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
            'target_actionable' => $target !== null && ! isset($inbox->payload['resolved_at']) && ($definition['actionable'] ?? in_array($status, $definition['pending'], true)),
            'unread_active' => $inbox->read_at === null && $target !== null && ! isset($inbox->payload['resolved_at']) && ($definition['pending'] === [] || in_array($status, $definition['pending'], true)),
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
