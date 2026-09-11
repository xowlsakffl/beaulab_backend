<?php

namespace App\Domains\BeautyExpert\Actions\Staff;

use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\Common\OperationHistory\Support\OperationHistoryDisplayValue;
use Illuminate\Database\Eloquent\Model;

final class BeautyExpertUpdateHistoryRecordAction
{
    public function __construct(
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    public function capture(BeautyExpert $expert): array
    {
        $expert->loadMissing([
            'beauty',
            'profileImage',
            'educationCertificateImages',
            'etcCertificateImages',
            'categories',
        ]);

        return [
            'beauty' => $this->item('뷰티업체', (int) $expert->beauty_id, $expert->beauty?->name ?? (string) $expert->beauty_id),
            'sort_order' => $this->item('정렬순서', (int) $expert->sort_order, (string) (int) $expert->sort_order),
            'name' => $this->item('전문가명', $expert->name, $expert->name),
            'gender' => $this->item('성별', $expert->gender, $expert->gender),
            'position' => $this->item('직책', $expert->position, $expert->position),
            'career_started_at' => $this->item('경력 시작일', $expert->career_started_at?->toDateString(), $expert->career_started_at?->toDateString()),
            'educations' => $this->item('학력사항', $expert->educations ?? [], OperationHistoryDisplayValue::lines($expert->educations ?? [])),
            'careers' => $this->item('경력사항', $expert->careers ?? [], OperationHistoryDisplayValue::lines($expert->careers ?? [])),
            'etc_contents' => $this->item('활동사항', $expert->etc_contents ?? [], OperationHistoryDisplayValue::lines($expert->etc_contents ?? [])),
            'status' => $this->item('상태', $expert->status, $expert->status),
            'allow_status' => $this->item('검수상태', $expert->allow_status, $expert->allow_status),
            'categories' => $this->item('카테고리', $this->categoryValue($expert), $this->categoryDisplay($expert)),
            'profile_image' => $this->item('프로필 이미지', $expert->profileImage?->path, OperationHistoryDisplayValue::fileName($expert->profileImage?->path)),
            'education_certificate_images' => $this->item('학력 증명서', $this->mediaList($expert->educationCertificateImages), $this->mediaListDisplay($expert->educationCertificateImages)),
            'etc_certificate_images' => $this->item('활동 증명서', $this->mediaList($expert->etcCertificateImages), $this->mediaListDisplay($expert->etcCertificateImages)),
        ];
    }

    public function recordCreated(BeautyExpert $expert): void
    {
        $this->record($expert, OperationHistory::ACTION_CREATED, 'staff.beauty_expert.create', []);
    }

    /**
     * @param  array<string, array{label:string,value:mixed,display:?string}>  $before
     */
    public function recordUpdated(BeautyExpert $expert, array $before): void
    {
        foreach (OperationHistoryChangeSetBuilder::groupedFromSnapshots($before, $this->capture($expert), ['allow_status', 'status']) as $action => $changes) {
            $this->record($expert, $action, 'staff.beauty_expert.update', $changes);
        }
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
    private function categoryValue(BeautyExpert $expert): array
    {
        return $expert->categories
            ->map(static fn ($category): array => [
                'id' => (int) $category->id,
                'path' => (string) ($category->full_path ?: $category->name),
                'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
            ])
            ->sortBy('path')
            ->values()
            ->all();
    }

    private function categoryDisplay(BeautyExpert $expert): ?string
    {
        return OperationHistoryDisplayValue::lines(collect($this->categoryValue($expert))
            ->map(static fn (array $category): string => ($category['is_primary'] ? '[대표] ' : '').$category['path'])
            ->all());
    }

    /**
     * @return array<int, array{id:int,path:string}>
     */
    private function mediaList($media): array
    {
        return $media
            ->map(static fn ($item): array => [
                'id' => (int) $item->id,
                'path' => (string) $item->path,
            ])
            ->values()
            ->all();
    }

    private function mediaListDisplay($media): ?string
    {
        return OperationHistoryDisplayValue::lines(collect($this->mediaList($media))
            ->map(static fn (array $item): string => OperationHistoryDisplayValue::fileName($item['path']))
            ->all());
    }

    /**
     * @param  array<int, array<string, mixed>>  $changes
     */
    private function record(BeautyExpert $expert, string $action, string $source, array $changes): void
    {
        if ($changes === [] && $action !== OperationHistory::ACTION_CREATED) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $expert,
            action: $action,
            actor: $actor instanceof Model ? $actor : null,
            reason: null,
            metadata: ['source' => $source],
            changes: $changes,
        );
    }
}
