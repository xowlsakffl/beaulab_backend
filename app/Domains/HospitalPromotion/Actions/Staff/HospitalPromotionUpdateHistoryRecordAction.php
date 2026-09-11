<?php

namespace App\Domains\HospitalPromotion\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\Common\OperationHistory\Support\OperationHistoryDisplayValue;
use App\Domains\HospitalPromotion\Models\HospitalPromotion;

final class HospitalPromotionUpdateHistoryRecordAction
{
    public function __construct(private readonly OperationHistoryCreateAction $historyCreateAction) {}

    public function capture(HospitalPromotion $promotion): array
    {
        $promotion->loadMissing(['banner', 'editorImages']);
        $snapshot = [];
        foreach (['title' => '프로모션명', 'content' => '내용', 'side' => '게시위치', 'slot' => '노출순서', 'start_date' => '게시 시작일', 'end_date' => '게시 종료일', 'status' => '공개여부'] as $key => $label) {
            $value = in_array($key, ['start_date', 'end_date'], true) ? $promotion->{$key}->toDateString() : $promotion->{$key};
            $display = match ($key) {
                'status' => $value === 'ACTIVE' ? '공개' : '비공개',
                'side' => $value === 'LEFT' ? '왼쪽' : '오른쪽',
                default => (string) $value,
            };
            $snapshot[$key] = compact('label', 'value', 'display');
        }
        foreach (['banner' => '배너 이미지', 'editorImages' => '본문 이미지'] as $relation => $label) {
            $media = $relation === 'banner' ? collect($promotion->banner ? [$promotion->banner] : []) : $promotion->editorImages;
            $value = $media->map(fn ($item) => ['id' => (int) $item->id, 'path' => (string) $item->path])->values()->all();
            $display = OperationHistoryDisplayValue::lines($media->map(fn ($item) => OperationHistoryDisplayValue::fileName($item->path))->all());
            $snapshot[$relation === 'banner' ? 'banner' : 'editor_images'] = compact('label', 'value', 'display');
        }

        return $snapshot;
    }

    public function record(HospitalPromotion $promotion, ?array $before): void
    {
        $after = $this->capture($promotion);
        $groups = $before === null
            ? [OperationHistory::ACTION_CREATED => OperationHistoryChangeSetBuilder::fromSnapshots([], $after)]
            : OperationHistoryChangeSetBuilder::groupedFromSnapshots($before, $after, ['status']);
        foreach ($groups as $action => $changes) {
            $this->historyCreateAction->execute(
                target: $promotion, action: $action, actor: auth()->user(),
                metadata: ['source' => $before === null ? 'staff.hospital_promotion.create' : 'staff.hospital_promotion.update'],
                changes: $changes,
            );
        }
    }
}
