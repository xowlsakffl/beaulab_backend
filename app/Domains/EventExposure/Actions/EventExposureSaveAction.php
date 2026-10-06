<?php

namespace App\Domains\EventExposure\Actions;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\EventExposure\Dto\EventExposureDto;
use App\Domains\EventExposure\Models\EventExposure;
use App\Domains\EventExposure\Queries\EventExposureQuery;
use App\Domains\HospitalEventAd\Queries\Staff\HospitalEventAdSlotAvailabilityForStaffQuery;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class EventExposureSaveAction
{
    public function __construct(private readonly EventExposureQuery $query, private readonly HospitalEventAdSlotAvailabilityForStaffQuery $bookings, private readonly OperationHistoryCreateAction $history) {}

    public function save(array $payload, ?EventExposure $model = null): array
    {
        $model ? Gate::authorize('update', $model) : Gate::authorize('create', EventExposure::class);

        return DB::transaction(function () use ($payload, $model) {
            $this->bookings->lockBookings();
            $creating = ! $model;
            if ($model) {
                $model = EventExposure::query()->lockForUpdate()->findOrFail($model->id);
                if (($payload['expected_updated_at'] ?? null) !== $model->updated_at->toISOString()) {
                    $this->invalid('expected_updated_at', '다른 사용자가 수정했습니다. 최신 내용을 다시 조회해 주세요.');
                }
                [$placement,$categoryId] = array_pad(explode(':', $model->position_key), 2, null);
            } else {
                $placement = $payload['placement'];
                $categoryId = $payload['category_id'] ?? null;
                $model = new EventExposure;
                $model->created_by_staff_id = auth()->id();
            }
            $scope = $this->query->scope($placement, $categoryId ? (int) $categoryId : null);
            DB::table('event_exposure_positions')->insertOrIgnore(['key' => $scope['key'], 'revision' => 0]);
            $before = $creating ? [] : $this->snapshot($model);
            $model->position_key = $scope['key'];
            foreach (['hospital_event_id', 'start_at', 'end_at', 'status'] as $field) {
                if (array_key_exists($field, $payload)) {
                    $model->{$field} = in_array($field, ['start_at', 'end_at'], true)
                        ? CarbonImmutable::parse($payload[$field])->setTimezone(config('app.timezone'))->startOfSecond()
                        : $payload[$field];
                }
            }
            if ($creating) {
                $model->status = 'ACTIVE';
            }
            $now = CarbonImmutable::now()->startOfSecond();
            if (($creating || $model->isDirty('start_at')) && $model->start_at < $now) {
                $this->invalid('start_at', '시작 시각이 지났어요. 현재 이후의 시간을 선택해 주세요.');
            }
            if ($model->end_at < $model->start_at) {
                $this->invalid('end_at', '종료 시각은 시작 시각 이후로 선택해 주세요.');
            }
            if ($creating || isset($payload['hospital_event_id']) || $model->status === 'ACTIVE') {
                $event = $this->query->events($scope)->whereKey($model->hospital_event_id)->lockForUpdate()->first();
                if (! $event) {
                    $this->invalid('hospital_event_id', '선택한 위치에 정상 노출 가능한 이벤트를 선택해 주세요.');
                }
                if (($event->event_start_at && $model->start_at < $event->event_start_at) || (! $event->is_event_period_unlimited && $event->event_end_at && $model->end_at > $event->event_end_at->copy()->endOfDay())) {
                    $this->invalid('start_at', '게시기간은 이벤트 진행기간 안에서 선택해 주세요.');
                }
                if ($creating || $model->isDirty(['hospital_event_id', 'start_at', 'end_at', 'status'])) {
                    $duplicate = EventExposure::query()->where('position_key', $scope['key'])->where('hospital_event_id', $model->hospital_event_id)->when(! $creating, fn ($q) => $q->whereKeyNot($model->id))->where('start_at', '<=', $model->end_at)->where('end_at', '>=', $model->start_at)->exists();
                    $adDuplicate = $this->query->ads($scope)->where('hospital_event_id', $model->hospital_event_id)->where('start_at', '<=', $model->end_at)->where('end_at', '>=', $model->start_at)->exists();
                    if ($duplicate || $adDuplicate) {
                        $this->invalid('hospital_event_id', '이 위치에 같은 기간으로 등록된 이벤트입니다.');
                    }
                }
            }
            if ($model->status === 'ACTIVE' && ($creating || $model->isDirty(['hospital_event_id', 'start_at', 'end_at', 'status']))) {
                $this->capacity($scope, $model);
            }
            $model->updated_at = now()->max($model->updated_at?->copy()->addMicrosecond() ?? now());
            $model->save();
            $after = $this->snapshot($model);
            foreach ($creating ? ['CREATED' => OperationHistoryChangeSetBuilder::fromSnapshots([], $after)] : OperationHistoryChangeSetBuilder::groupedFromSnapshots($before, $after, ['status']) as $action => $changes) {
                $this->history->execute(target: $model, action: $action, actor: auth()->user(), changes: $changes);
            }

            return EventExposureDto::detail($model->fresh());
        });
    }

    private function capacity(array $scope, EventExposure $model): void
    {
        $manual = EventExposure::query()->where('position_key', $scope['key'])->where('status', 'ACTIVE')->when($model->exists, fn ($q) => $q->whereKeyNot($model->id))->where('start_at', '<=', $model->end_at)->where('end_at', '>=', $model->start_at)->get(['start_at', 'end_at']);
        $ads = $this->query->ads($scope)->where('start_at', '<=', $model->end_at)->where('end_at', '>=', $model->start_at)->get(['start_at', 'end_at']);
        $changes = [];
        $start = $model->start_at->timestamp;
        $end = $model->end_at->timestamp;
        foreach ([...$manual, ...$ads] as $row) {
            $from = max($start, $row->start_at->timestamp);
            $until = min($end, $row->end_at->timestamp) + 1;
            $changes[$from] = ($changes[$from] ?? 0) + 1;
            $changes[$until] = ($changes[$until] ?? 0) - 1;
        }
        ksort($changes, SORT_NUMERIC);
        $count = 0;
        foreach ($changes as $time => $delta) {
            $count += $delta;
            if ($count >= EventExposure::LIMIT) {
                $label = CarbonImmutable::createFromTimestamp($time, config('app.timezone'))->format('Y.m.d H:i');
                $this->invalid('start_at', "{$label}에 광고 포함 {$count}개가 예약되어 있어요. 다른 게시기간을 선택해 주세요.");
            }
        }
    }

    public function reorder(array $payload): void
    {
        Gate::authorize('update', EventExposure::class);
        DB::transaction(function () use ($payload) {
            $this->bookings->lockBookings();
            $scope = $this->query->scope($payload['placement'], $payload['category_id'] ?? null);
            $position = $this->query->position($scope);
            $current = $this->query->activeKeys($scope, now());
            $actual = $payload['keys'];
            sort($current);
            sort($actual);
            if ((int) ($position?->revision ?? 0) !== (int) $payload['revision'] || $current !== $actual) {
                $this->invalid('keys', '노출 목록이 변경됐습니다. 최신 목록에서 다시 변경해 주세요.');
            }
            DB::table('event_exposure_positions')->updateOrInsert(['key' => $scope['key']], ['ordered_keys' => json_encode($payload['keys']), 'revision' => (int) ($position?->revision ?? 0) + 1]);
        });
    }

    private function snapshot(EventExposure $model): array
    {
        $result = [];
        foreach (['hospital_event_id' => '이벤트 ID', 'start_at' => '게시 시작일시', 'end_at' => '게시 종료일시', 'status' => '공개여부'] as $key => $label) {
            $value = (string) $model->{$key};
            $result[$key] = ['label' => $label, 'value' => $value, 'display' => match ($value) {
                'ACTIVE' => '공개', 'INACTIVE' => '비공개', default => $value,
            }];
        }

        return $result;
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
