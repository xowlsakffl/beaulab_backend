<?php

namespace App\Domains\ReviewExposure\Actions;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\ReviewExposure\Dto\ReviewExposureDto;
use App\Domains\ReviewExposure\Models\ReviewExposure;
use App\Domains\ReviewExposure\Queries\ReviewExposureQuery;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ReviewExposureSaveAction
{
    public function __construct(private readonly ReviewExposureQuery $query, private readonly OperationHistoryCreateAction $history) {}

    public function save(array $payload, ?ReviewExposure $model = null): array
    {
        $model ? Gate::authorize('update', $model) : Gate::authorize('create', ReviewExposure::class);

        return DB::transaction(function () use ($payload, $model) {

            $creating = ! $model;
            $this->lockPositions();
            if ($model) {
                $model = ReviewExposure::query()->lockForUpdate()->findOrFail($model->id);
                if (($payload['expected_updated_at'] ?? null) !== $model->updated_at->toISOString()) {
                    $this->invalid('expected_updated_at', '다른 사용자가 수정했습니다. 최신 내용을 다시 조회해 주세요.');
                }
                [$placement,$categoryId] = array_pad(explode(':', $model->position_key), 2, null);
            } else {
                $placement = $payload['placement'];
                $categoryId = $payload['category_id'] ?? null;
                $model = new ReviewExposure;
                $model->created_by_staff_id = auth()->id();
            }
            $scope = $this->query->scope($placement, $categoryId ? (int) $categoryId : null);
            DB::table('review_exposure_positions')->insertOrIgnore(['key' => $scope['key'], 'revision' => 0]);
            $before = $creating ? [] : $this->snapshot($model);
            $model->position_key = $scope['key'];
            foreach (['hospital_review_id', 'start_at', 'end_at', 'status'] as $field) {
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
            if ($creating || isset($payload['hospital_review_id']) || $model->status === 'ACTIVE') {
                $review = $this->query->reviews($scope)->whereKey($model->hospital_review_id)->lockForUpdate()->first();
                if (! $review) {
                    $this->invalid('hospital_review_id', '선택한 위치에 정상 노출 가능한 후기를 선택해 주세요.');
                }
                $duplicate = ReviewExposure::query()->where('position_key', $scope['key'])->where('hospital_review_id', $model->hospital_review_id)
                    ->when(! $creating, fn ($q) => $q->whereKeyNot($model->id))->where('start_at', '<=', $model->end_at)->where('end_at', '>=', $model->start_at)->exists();
                if ($duplicate) {
                    $this->invalid('hospital_review_id', '이 위치에 같은 기간으로 등록된 후기입니다.');
                }
            }
            if ($model->status === 'ACTIVE' && ($creating || $model->isDirty(['hospital_review_id', 'start_at', 'end_at', 'status']))) {
                $this->capacity($scope, $model);
            }
            $model->updated_at = now()->max($model->updated_at?->copy()->addMicrosecond() ?? now());
            $model->save();
            $after = $this->snapshot($model);
            foreach ($creating ? ['CREATED' => OperationHistoryChangeSetBuilder::fromSnapshots([], $after)] : OperationHistoryChangeSetBuilder::groupedFromSnapshots($before, $after, ['status']) as $action => $changes) {
                $this->history->execute(target: $model, action: $action, actor: auth()->user(), changes: $changes);
            }

            return ReviewExposureDto::detail($model->fresh());
        });
    }

    private function capacity(array $scope, ReviewExposure $model): void
    {
        $manual = ReviewExposure::query()->where('position_key', $scope['key'])->where('status', 'ACTIVE')->when($model->exists, fn ($q) => $q->whereKeyNot($model->id))->where('start_at', '<=', $model->end_at)->where('end_at', '>=', $model->start_at)->get(['start_at', 'end_at']);
        $changes = [];
        $start = $model->start_at->timestamp;
        $end = $model->end_at->timestamp;
        foreach ($manual as $row) {
            $from = max($start, $row->start_at->timestamp);
            $until = min($end, $row->end_at->timestamp) + 1;
            $changes[$from] = ($changes[$from] ?? 0) + 1;
            $changes[$until] = ($changes[$until] ?? 0) - 1;
        }
        ksort($changes, SORT_NUMERIC);
        $count = 0;
        foreach ($changes as $time => $delta) {
            $count += $delta;
            if ($count >= ReviewExposure::LIMIT) {
                $label = CarbonImmutable::createFromTimestamp($time, config('app.timezone'))->format('Y.m.d H:i');
                $this->invalid('start_at', "{$label}에 후기 {$count}개가 예약되어 있어요. 다른 게시기간을 선택해 주세요.");
            }
        }
    }

    public function reorder(array $payload): void
    {
        Gate::authorize('update', ReviewExposure::class);
        DB::transaction(function () use ($payload) {
            $this->lockPositions();

            $scope = $this->query->scope($payload['placement'], $payload['category_id'] ?? null);
            $position = $this->query->position($scope);
            $current = $this->query->activeKeys($scope, now());
            $actual = $payload['keys'];
            sort($current);
            sort($actual);
            if ((int) ($position?->revision ?? 0) !== (int) $payload['revision'] || $current !== $actual) {
                $this->invalid('keys', '노출 목록이 변경됐습니다. 최신 목록에서 다시 변경해 주세요.');
            }
            DB::table('review_exposure_positions')->updateOrInsert(['key' => $scope['key']], ['ordered_keys' => json_encode($payload['keys']), 'revision' => (int) ($position?->revision ?? 0) + 1]);
        });
    }

    private function lockPositions(): void
    {
        // 성형·쁘띠 단위 잠금으로 신규 위치 생성과 예약 한도 검사를 직렬화한다.
        foreach (ReviewExposure::PLACEMENTS as $placement) {
            DB::table('review_exposure_positions')->where('key', $placement)->lockForUpdate()->firstOrFail();
        }
    }

    private function snapshot(ReviewExposure $model): array
    {
        $result = [];
        foreach (['hospital_review_id' => '후기 ID', 'start_at' => '게시 시작일시', 'end_at' => '게시 종료일시', 'status' => '공개여부'] as $key => $label) {
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
