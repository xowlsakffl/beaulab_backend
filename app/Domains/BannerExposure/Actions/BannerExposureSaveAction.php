<?php

namespace App\Domains\BannerExposure\Actions;

use App\Domains\BannerExposure\Dto\BannerExposureDto;
use App\Domains\BannerExposure\Models\BannerExposure;
use App\Domains\BannerExposure\Queries\BannerExposureQuery;
use App\Domains\BannerExposure\Queries\BannerExposureTargetQuery;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalEventAd\Queries\Staff\HospitalEventAdSlotAvailabilityForStaffQuery;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class BannerExposureSaveAction
{
    public function __construct(
        private readonly BannerExposureQuery $query,
        private readonly BannerExposureTargetQuery $targets,
        private readonly HospitalEventAdSlotAvailabilityForStaffQuery $booking,
        private readonly MediaAttachDeleteAction $media,
        private readonly OperationHistoryCreateAction $history,
    ) {}

    public function save(array $payload, ?BannerExposure $banner = null): array
    {
        $banner ? Gate::authorize('update', $banner) : Gate::authorize('create', BannerExposure::class);

        return DB::transaction(function () use ($payload, $banner): array {
            // 광고 승인과 직접 등록이 같은 예약 잠금을 사용한다. 광고 자체에는 6개 제한을 적용하지 않는다.
            $this->booking->lockBookings();
            $before = null;
            if ($banner) {
                $banner = BannerExposure::query()->lockForUpdate()->findOrFail($banner->id);
                $this->assertVersion($banner, $payload);
                $before = $this->snapshot($banner);
            } else {
                $banner = new BannerExposure;
                $banner->created_by_staff_id = auth()->id();
            }
            $creating = ! $banner->exists;
            $banner->fill(Arr::except($payload, ['banner', 'target_id', 'expected_updated_at']));
            if (isset($payload['target_type'])) {
                $banner->hospital_id = $banner->hospital_event_id = $banner->notice_id = null;
                $column = match ($payload['target_type']) {
                    'HOSPITAL' => 'hospital_id', 'EVENT' => 'hospital_event_id', 'NOTICE' => 'notice_id', default => null,
                };
                if ($column) {
                    $banner->{$column} = $payload['target_id'] ?? null;
                    $banner->external_url = null;
                    $banner->open_mode = 'IN_APP';
                }
                if ($banner->connection_type === 'PAGE') {
                    $banner->category_id = null;
                }
            }
            foreach (['start_at', 'end_at'] as $key) {
                if (isset($payload[$key])) {
                    $banner->{$key} = CarbonImmutable::parse($payload[$key])->setTimezone(config('app.timezone'));
                }
            }
            $now = CarbonImmutable::now(config('app.timezone'))->startOfSecond();
            if (($creating || $banner->isDirty('start_at')) && $banner->start_at < $now) {
                $this->invalid('start_at', '시작 시각이 지났어요. 현재 이후의 시간을 선택해 주세요.');
            }
            $banner->title = trim($banner->title);
            if ($banner->title === '') {
                $this->invalid('title', '배너명을 입력해 주세요.');
            }
            if ($banner->start_at > $banner->end_at) {
                $this->invalid('end_at', '게시 종료일시는 시작일시 이후여야 합니다.');
            }
            // 비공개 전환은 연결 대상이 중지된 상태에서도 허용한다.
            if ($creating || isset($payload['target_type']) || $banner->status === 'ACTIVE') {
                $this->validateTarget($banner);
            }
            $scheduleChanged = $creating || $banner->isDirty(['placement', 'start_at', 'end_at', 'status', 'target_type', 'hospital_id', 'hospital_event_id', 'notice_id', 'category_id']);
            if ($scheduleChanged && $banner->status === 'ACTIVE') {
                $this->assertCapacity($banner);
            }
            $banner->updated_at = now()->max($banner->updated_at?->copy()->addMicrosecond() ?? now());
            $banner->save();
            if (isset($payload['banner'])) {
                $this->media->deleteCollectionMedia($banner, 'banner');
                $this->media->attachOne($banner, $payload['banner'], 'banner', 'banner-exposure', 'banner', true);
                $banner->unsetRelation('image');
            }
            $after = $this->snapshot($banner);
            $groups = $creating
                ? ['CREATED' => OperationHistoryChangeSetBuilder::fromSnapshots([], $after)]
                : OperationHistoryChangeSetBuilder::groupedFromSnapshots($before, $after, ['status']);
            foreach ($groups as $action => $changes) {
                $this->history->execute(target: $banner, action: $action, actor: auth()->user(), changes: $changes);
            }

            return BannerExposureDto::detail($banner->fresh(BannerExposure::RELATIONS));
        });
    }

    public function delete(BannerExposure $banner, array $payload): void
    {
        Gate::authorize('delete', $banner);
        DB::transaction(function () use ($banner, $payload): void {
            $this->booking->lockBookings();
            $banner = BannerExposure::query()->lockForUpdate()->findOrFail($banner->id);
            $this->assertVersion($banner, $payload);
            $this->history->execute(target: $banner, action: 'DELETED', actor: auth()->user());
            $this->media->deleteCollectionMedia($banner, 'banner');
            $banner->delete();
        });
    }

    public function reorder(array $payload): void
    {
        Gate::authorize('update', BannerExposure::class);
        DB::transaction(function () use ($payload): void {
            $this->booking->lockBookings();
            $placement = $this->query->placement($payload['placement']);
            $current = $this->query->activeKeys($payload['placement'], now());
            $keys = $payload['keys'];
            $expected = $current;
            sort($expected);
            $actual = $keys;
            sort($actual);
            if ((int) $placement->revision !== (int) $payload['revision'] || $actual !== $expected) {
                $this->invalid('keys', '노출 목록 또는 순서가 변경됐습니다. 목록을 새로 조회한 후 변경해 주세요.');
            }
            DB::table('banner_exposure_placements')->where('code', $payload['placement'])->update([
                'ordered_keys' => json_encode($keys), 'revision' => (int) $placement->revision + 1,
            ]);
        });
    }

    private function validateTarget(BannerExposure $banner): void
    {
        if ($banner->target_type === 'EXTERNAL') {
            if ($banner->connection_type !== 'PAGE' || ! $banner->external_url) {
                $this->invalid('external_url', '연결할 HTTPS 주소를 입력해 주세요.');
            }

            return;
        }
        $id = $banner->hospital_id ?? $banner->hospital_event_id ?? $banner->notice_id;
        $target = $this->targets->eligible($banner->target_type)->whereKey($id)->lockForUpdate()->first();
        if (! $target) {
            $this->invalid('target_id', '정상 노출 가능한 연결 대상을 선택해 주세요.');
        }
        if ($banner->connection_type === 'EXHIBITION') {
            if ($banner->target_type !== 'EVENT' || ! $this->targets->categories()->whereKey($banner->category_id)->exists()
                || ! $target->categories()->whereKey($banner->category_id)->exists()) {
                $this->invalid('category_id', '선택한 기획전에 속한 이벤트를 선택해 주세요.');
            }
        }
        if ($banner->target_type === 'EVENT') {
            if (($target->event_start_at && $banner->start_at < $target->event_start_at)
                || (! $target->is_event_period_unlimited && $target->event_end_at && $banner->end_at > $target->event_end_at->copy()->endOfDay())) {
                $this->invalid('start_at', '게시기간은 연결 이벤트의 진행기간 안에서 설정해 주세요.');
            }
        }
    }

    private function assertCapacity(BannerExposure $banner): void
    {
        $start = $banner->start_at->timestamp;
        $end = $banner->end_at->timestamp;
        $manual = BannerExposure::query()->where('placement', $banner->placement)->where('status', 'ACTIVE')
            ->when($banner->exists, fn ($query) => $query->whereKeyNot($banner->id))
            ->where('start_at', '<=', $banner->end_at)->where('end_at', '>=', $banner->start_at)->get(['start_at', 'end_at']);
        $ads = HospitalEventAd::query()->where('placement', $banner->placement)->where('allow_status', 'APPROVED')
            ->where('start_at', '<=', $banner->end_at)->where('end_at', '>=', $banner->start_at)->get(['start_at', 'end_at']);
        $changes = [];
        foreach ([...$manual, ...$ads] as $row) {
            $from = max($start, $row->start_at->timestamp);
            $until = min($end, $row->end_at->timestamp) + 1;
            $changes[$from] = ($changes[$from] ?? 0) + 1;
            $changes[$until] = ($changes[$until] ?? 0) - 1;
        }
        ksort($changes, SORT_NUMERIC);
        $times = array_keys($changes);
        $count = 0;
        foreach ($times as $index => $time) {
            $count += $changes[$time];
            if ($count >= BannerExposure::LIMIT) {
                $until = min($end, ($times[$index + 1] ?? ($end + 1)) - 1);
                $fromLabel = CarbonImmutable::createFromTimestamp($time, config('app.timezone'))->format('Y.m.d H:i:s');
                $untilLabel = CarbonImmutable::createFromTimestamp($until, config('app.timezone'))->format('Y.m.d H:i:s');
                $this->invalid('start_at', "{$fromLabel} ~ {$untilLabel}에는 광고 포함 {$count}개가 예약되어 있어요. 이 구간을 피해 게시기간을 선택해 주세요.");
            }
        }
    }

    private function assertVersion(BannerExposure $banner, array $payload): void
    {
        if (($payload['expected_updated_at'] ?? null) !== $banner->updated_at?->toISOString()) {
            $this->invalid('expected_updated_at', '다른 사용자가 수정했습니다. 최신 내용을 다시 조회해 주세요.');
        }
    }

    private function snapshot(BannerExposure $banner): array
    {
        $snapshot = [];
        foreach (['title' => '배너명', 'connection_type' => '연결유형', 'target_type' => '연결 대상', 'hospital_id' => '병의원 ID', 'hospital_event_id' => '이벤트 ID', 'notice_id' => '공지 ID', 'category_id' => '기획전', 'external_url' => '외부 링크', 'open_mode' => '열기 방식', 'start_at' => '게시 시작일시', 'end_at' => '게시 종료일시', 'status' => '공개여부'] as $key => $label) {
            $value = (string) $banner->{$key};
            $display = match ($value) {
                'ACTIVE' => '공개', 'INACTIVE' => '비공개', 'PAGE' => '페이지', 'EXHIBITION' => '기획전',
                'HOSPITAL' => '병의원', 'EVENT' => '이벤트', 'NOTICE' => '공지사항', 'EXTERNAL' => '외부 링크',
                'IN_APP' => '앱 내 열기', 'BROWSER' => '외부 브라우저', default => $value,
            };
            $snapshot[$key] = compact('label', 'value', 'display');
        }
        $banner->loadMissing('image');
        $value = $banner->image?->path;
        $snapshot['image'] = ['label' => '배너 이미지', 'value' => $value, 'display' => $value ? basename($value) : ''];

        return $snapshot;
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
