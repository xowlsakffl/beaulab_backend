<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Database\Eloquent\Model;

final class HospitalVideoUpdateHistoryRecordAction
{
    public function __construct(
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    public function capture(HospitalVideo $video): array
    {
        $video->loadMissing(['hospital', 'doctor', 'categories', 'thumbnailMedia', 'videoFileMedia']);

        return [
            'hospital' => $this->item('병의원', (int) $video->hospital_id, $video->hospital?->name ?? (string) $video->hospital_id),
            'doctor' => $this->item('의료진', $video->doctor_id ? (int) $video->doctor_id : null, $video->doctor?->name),
            'title' => $this->item('제목', $video->title, $video->title),
            'description' => $this->item('설명', $video->description, $video->description),
            'distribution_channel' => $this->item('노출 채널', $video->distribution_channel, $video->distribution_channel),
            'external_video_id' => $this->item('외부 영상 ID', $video->external_video_id, $video->external_video_id),
            'external_video_url' => $this->item('외부 영상 URL', $video->external_video_url, $video->external_video_url),
            'duration_seconds' => $this->item('재생 시간', (int) $video->duration_seconds, (string) (int) $video->duration_seconds),
            'status' => $this->item('노출여부', $video->status, $video->status),
            'allow_status' => $this->item('검수상태', $video->allow_status, $video->allow_status),
            'publish_period' => $this->item('게시기간', [
                'is_unlimited' => (bool) $video->is_publish_period_unlimited,
                'start' => $video->publish_start_at?->toDateString(),
                'end' => $video->publish_end_at?->toDateString(),
            ], $this->periodLabel($video)),
            'categories' => $this->item('카테고리', $this->categoryValue($video), $this->categoryDisplay($video)),
            'thumbnail' => $this->item('썸네일', $video->thumbnailMedia?->path, $this->mediaLabel($video->thumbnailMedia?->path)),
            'video_file' => $this->item('동영상 파일', $video->videoFileMedia?->path, $this->mediaLabel($video->videoFileMedia?->path)),
        ];
    }

    public function recordCreated(HospitalVideo $video): void
    {
        $this->record(
            video: $video,
            action: OperationHistory::ACTION_CREATED,
            source: 'staff.hospital_video.create',
            changes: OperationHistoryChangeSetBuilder::single(
                key: 'created',
                label: '생성',
                before: null,
                after: OperationHistory::ACTION_CREATED,
                beforeDisplay: null,
                afterDisplay: '생성',
            ),
        );
    }

    /**
     * @param array<string, array{label:string,value:mixed,display:?string}> $before
     */
    public function recordUpdated(HospitalVideo $video, array $before): void
    {
        $changes = OperationHistoryChangeSetBuilder::fromSnapshots($before, $this->capture($video));
        if ($changes === []) {
            return;
        }

        $this->record(
            video: $video,
            action: OperationHistory::ACTION_UPDATED,
            source: 'staff.hospital_video.update',
            changes: $changes,
        );
    }

    /**
     * @return array{label:string,value:mixed,display:?string}
     */
    private function item(string $label, mixed $value, ?string $display): array
    {
        return compact('label', 'value', 'display');
    }

    /**
     * @return array<int, array{id:int,path:string,is_primary:bool}>
     */
    private function categoryValue(HospitalVideo $video): array
    {
        return $video->categories
            ->map(static fn ($category): array => [
                'id' => (int) $category->id,
                'path' => (string) ($category->full_path ?: $category->name),
                'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
            ])
            ->sortBy('path')
            ->values()
            ->all();
    }

    private function categoryDisplay(HospitalVideo $video): ?string
    {
        return $this->lineList(collect($this->categoryValue($video))
            ->map(static fn (array $category): string => ($category['is_primary'] ? '[대표] ' : '').$category['path'])
            ->all());
    }

    private function periodLabel(HospitalVideo $video): string
    {
        $startAt = $video->publish_start_at?->format('y.m.d') ?? '-';
        if ((bool) $video->is_publish_period_unlimited) {
            return "{$startAt} ~ 무기한";
        }

        return "{$startAt} ~ ".($video->publish_end_at?->format('y.m.d') ?? '-');
    }

    private function mediaLabel(?string $path): ?string
    {
        return $path ? basename($path) : null;
    }

    /**
     * @param array<int, mixed> $items
     */
    private function lineList(array $items): ?string
    {
        $items = collect($items)
            ->map(static fn (mixed $item): string => trim((string) $item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->values()
            ->all();

        return $items === [] ? null : implode("\n", $items);
    }

    /**
     * @param array<int, array<string, mixed>> $changes
     */
    private function record(HospitalVideo $video, string $action, string $source, array $changes): void
    {
        if ($changes === []) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $video,
            action: $action,
            actor: $actor instanceof Model ? $actor : null,
            reason: null,
            metadata: ['source' => $source],
            changes: $changes,
        );
    }
}
