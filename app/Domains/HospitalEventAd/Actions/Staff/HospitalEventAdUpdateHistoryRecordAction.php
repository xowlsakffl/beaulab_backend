<?php

namespace App\Domains\HospitalEventAd\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Illuminate\Database\Eloquent\Model;

final class HospitalEventAdUpdateHistoryRecordAction
{
    public function __construct(
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    public function capture(HospitalEventAd $ad): array
    {
        $ad->loadMissing([
            'hospital',
            'hospitalEvent',
            'categories',
            'managerStaff',
            'adImage',
        ]);

        return $this->snapshot($ad);
    }

    public function recordCreated(HospitalEventAd $ad): void
    {
        $this->record(
            ad: $ad,
            action: OperationHistory::ACTION_CREATED,
            source: 'staff.hospital_event_ad.create',
            changes: [],
        );
    }

    /**
     * @param  array<string, array{label:string,value:mixed,display:?string}>  $before
     */
    public function recordUpdated(HospitalEventAd $ad, array $before): void
    {
        $changes = OperationHistoryChangeSetBuilder::fromSnapshots($before, $this->capture($ad));
        if ($changes === []) {
            return;
        }

        $this->record(
            ad: $ad,
            action: OperationHistory::ACTION_UPDATED,
            source: 'staff.hospital_event_ad.update',
            changes: $changes,
        );
    }

    public function recordAllowStatusUpdated(
        HospitalEventAd $ad,
        string $beforeStatus,
        string $afterStatus,
        ?string $reason = null,
        bool $bulk = false,
    ): void {
        $this->record(
            ad: $ad,
            action: OperationHistory::ACTION_STATE_UPDATED,
            source: 'staff.hospital_event_ad.allow_status',
            changes: OperationHistoryChangeSetBuilder::single(
                key: 'allow_status',
                label: '검수상태',
                before: $beforeStatus,
                after: $afterStatus,
                beforeDisplay: HospitalEventAd::allowStatusLabel($beforeStatus),
                afterDisplay: HospitalEventAd::allowStatusLabel($afterStatus),
            ),
            reason: $reason,
            metadata: ['bulk' => $bulk],
        );
    }

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    private function snapshot(HospitalEventAd $ad): array
    {
        return [
            'hospital' => $this->item('병의원', (int) $ad->hospital_id, $ad->hospital?->name ?? (string) $ad->hospital_id),
            'hospital_event' => $this->item('이벤트', (int) $ad->hospital_event_id, $ad->hospitalEvent?->name ?? (string) $ad->hospital_event_id),
            'category' => $this->item('부위 카테고리', $this->categoryValue($ad), $this->categoryDisplay($ad)),
            'manager_staff' => $this->item('담당자', $ad->manager_staff_id ? (int) $ad->manager_staff_id : null, $ad->managerStaff?->name),
            'placement' => $this->item('광고위치', $ad->placement, HospitalEventAd::placementLabel((string) $ad->placement)),
            'cost' => $this->item('비용', (int) $ad->cost, $this->pointLabel((int) $ad->cost)),
            'period' => $this->item('광고기간', [
                'start_at' => $ad->start_at?->toISOString(),
                'end_at' => $ad->end_at?->toISOString(),
            ], $this->periodLabel($ad)),
            'ad_image' => $this->item('광고 이미지', $ad->adImage?->path, $this->mediaLabel($ad->adImage?->path)),
        ];
    }

    /**
     * @return array{label:string,value:mixed,display:?string}
     */
    private function item(string $label, mixed $value, ?string $display): array
    {
        return compact('label', 'value', 'display');
    }

    private function pointLabel(int $value): string
    {
        return number_format($value).'P';
    }

    private function periodLabel(HospitalEventAd $ad): string
    {
        return ($ad->start_at?->format('y.m.d H:i') ?? '-').' ~ '.($ad->end_at?->format('y.m.d H:i') ?? '-');
    }

    private function mediaLabel(?string $path): ?string
    {
        return $path ? basename($path) : null;
    }

    /**
     * @return array<int, array{id:int,path:string,is_primary:bool}>
     */
    private function categoryValue(HospitalEventAd $ad): array
    {
        if (! $ad->relationLoaded('categories')) {
            return [];
        }

        return $ad->categories
            ->map(static fn ($category): array => [
                'id' => (int) $category->id,
                'path' => (string) ($category->full_path ?: $category->name),
                'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
            ])
            ->sortBy('path')
            ->values()
            ->all();
    }

    private function categoryDisplay(HospitalEventAd $ad): ?string
    {
        $items = collect($this->categoryValue($ad))
            ->map(static fn (array $category): string => (string) $category['path'])
            ->filter()
            ->values()
            ->all();

        return $items === [] ? null : implode("\n", $items);
    }

    /**
     * @param  array<int, array<string, mixed>>  $changes
     */
    private function record(
        HospitalEventAd $ad,
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
            target: $ad,
            action: $action,
            actor: $actor instanceof Model ? $actor : null,
            reason: $reason,
            metadata: ['source' => $source, ...$metadata],
            changes: $changes,
        );
    }
}
