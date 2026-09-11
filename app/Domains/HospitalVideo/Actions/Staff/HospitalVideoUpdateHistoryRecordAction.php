<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\Common\OperationHistory\Support\OperationHistoryDisplayValue;
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
        $video->loadMissing(['hospital', 'doctor', 'managerStaff', 'categories', 'hashtags', 'thumbnailMedia']);

        return [
            'hospital' => $this->item('병의원', (int) $video->hospital_id, $video->hospital?->name ?? (string) $video->hospital_id),
            'doctor' => $this->item('의료진', $video->doctor_id ? (int) $video->doctor_id : null, $video->doctor?->name),
            'manager_staff' => $this->item('담당자', $video->manager_staff_id ? (int) $video->manager_staff_id : null, $video->managerStaff?->name),
            'title' => $this->item('동영상 제목', $video->title, $video->title),
            'description' => $this->item('영상 설명', $video->description, $video->description),
            'external_video_url' => $this->item('유튜브 링크', $video->external_video_url, $video->external_video_url),
            'duration_seconds' => $this->item('재생시간', (int) $video->duration_seconds, $this->durationLabel((int) $video->duration_seconds)),
            'hospital_status' => $this->item('공개여부', $video->hospital_status, HospitalVideo::hospitalStatusLabel((string) $video->hospital_status)),
            'admin_status' => $this->item('강제중지', $video->admin_status, HospitalVideo::adminStatusLabel((string) $video->admin_status)),
            'categories' => $this->item('카테고리', $this->categoryValue($video), $this->categoryDisplay($video)),
            'hashtags' => $this->item('해시태그', $this->hashtagValue($video), $this->hashtagDisplay($video)),
            'thumbnail' => $this->item('썸네일', $video->thumbnailMedia?->path, OperationHistoryDisplayValue::fileName($video->thumbnailMedia?->path)),
        ];
    }

    public function recordCreated(HospitalVideo $video): void
    {
        $this->record(
            video: $video,
            action: OperationHistory::ACTION_CREATED,
            source: 'staff.hospital_video.create',
            changes: [],
        );
    }

    /**
     * @param  array<string, array{label:string,value:mixed,display:?string}>  $before
     */
    public function recordUpdated(HospitalVideo $video, array $before): void
    {
        foreach (OperationHistoryChangeSetBuilder::groupedFromSnapshots($before, $this->capture($video), ['hospital_status', 'admin_status']) as $action => $changes) {
            $this->record($video, $action, 'staff.hospital_video.update', $changes);
        }
    }

    public function recordHospitalStatusUpdated(
        HospitalVideo $video,
        string $beforeStatus,
        string $afterStatus,
        ?string $reason = null,
    ): void {
        $this->record(
            video: $video,
            action: OperationHistory::ACTION_STATE_UPDATED,
            source: 'hospital.hospital_video.hospital_status',
            changes: OperationHistoryChangeSetBuilder::single(
                key: 'hospital_status',
                label: '공개여부',
                before: $beforeStatus,
                after: $afterStatus,
                beforeDisplay: HospitalVideo::hospitalStatusLabel($beforeStatus),
                afterDisplay: HospitalVideo::hospitalStatusLabel($afterStatus),
            ),
            reason: $reason,
        );
    }

    public function recordAdminStatusUpdated(
        HospitalVideo $video,
        string $beforeStatus,
        string $afterStatus,
        ?string $reason = null,
        bool $bulk = false,
    ): void {
        $this->record(
            video: $video,
            action: OperationHistory::ACTION_STATE_UPDATED,
            source: 'staff.hospital_video.admin_status',
            changes: OperationHistoryChangeSetBuilder::single(
                key: 'admin_status',
                label: '강제중지',
                before: $beforeStatus,
                after: $afterStatus,
                beforeDisplay: HospitalVideo::adminStatusLabel($beforeStatus),
                afterDisplay: HospitalVideo::adminStatusLabel($afterStatus),
            ),
            reason: $reason,
            metadata: ['bulk' => $bulk],
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
     * @return array<int, array{id:int,path:string}>
     */
    private function categoryValue(HospitalVideo $video): array
    {
        return $video->categories
            ->map(static fn ($category): array => [
                'id' => (int) $category->id,
                'path' => (string) ($category->full_path ?: $category->name),
            ])
            ->sortBy('path')
            ->values()
            ->all();
    }

    private function categoryDisplay(HospitalVideo $video): ?string
    {
        return OperationHistoryDisplayValue::lines(collect($this->categoryValue($video))
            ->map(static fn (array $category): string => $category['path'])
            ->all());
    }

    /**
     * @return array<int, array{id:int,name:string,sort_order:int}>
     */
    private function hashtagValue(HospitalVideo $video): array
    {
        return $video->hashtags
            ->map(static fn ($hashtag): array => [
                'id' => (int) $hashtag->id,
                'name' => (string) $hashtag->name,
                'sort_order' => (int) ($hashtag->pivot?->sort_order ?? 0),
            ])
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    private function hashtagDisplay(HospitalVideo $video): ?string
    {
        return OperationHistoryDisplayValue::lines(collect($this->hashtagValue($video))
            ->map(static fn (array $hashtag): string => '#'.$hashtag['name'])
            ->all());
    }

    private function durationLabel(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    /**
     * @param  array<int, array<string, mixed>>  $changes
     */
    private function record(
        HospitalVideo $video,
        string $action,
        string $source,
        array $changes,
        ?string $reason = null,
        array $metadata = [],
    ): void {
        if ($changes === [] && $action !== OperationHistory::ACTION_CREATED) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $video,
            action: $action,
            actor: $actor instanceof Model ? $actor : null,
            reason: $reason,
            metadata: ['source' => $source, ...$metadata],
            changes: $changes,
        );
    }
}
