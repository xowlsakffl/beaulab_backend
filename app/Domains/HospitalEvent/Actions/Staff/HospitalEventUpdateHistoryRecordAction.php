<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Database\Eloquent\Model;

final class HospitalEventUpdateHistoryRecordAction
{
    public function __construct(
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    public function capture(HospitalEvent $event): array
    {
        $event->loadMissing([
            'hospital',
            'categories',
            'doctors',
            'options',
            'thumbnailImage',
            'eventPageImage',
        ]);

        return $this->snapshot($event);
    }

    public function recordCreated(HospitalEvent $event): void
    {
        $this->record(
            event: $event,
            action: OperationHistory::ACTION_CREATED,
            source: 'staff.hospital_event.create',
            changes: [],
        );
    }

    /**
     * @param  array<string, array{label:string,value:mixed,display:?string}>  $before
     */
    public function recordUpdated(HospitalEvent $event, array $before): void
    {
        $event->load([
            'hospital',
            'categories',
            'doctors',
            'options',
            'thumbnailImage',
            'eventPageImage',
        ]);

        $changes = $this->changes($before, $this->snapshot($event));
        $this->record(
            event: $event,
            action: OperationHistory::ACTION_UPDATED,
            source: 'staff.hospital_event.update',
            changes: $changes,
        );
    }

    public function recordAdminStatusUpdated(
        HospitalEvent $event,
        string $beforeStatus,
        string $afterStatus,
        ?string $reason = null,
        bool $bulk = false,
    ): void {
        $this->record(
            event: $event,
            action: OperationHistory::ACTION_STATE_UPDATED,
            source: 'staff.hospital_event.admin_status',
            changes: OperationHistoryChangeSetBuilder::single(
                key: 'admin_status',
                label: '강제중지',
                before: $beforeStatus,
                after: $afterStatus,
                beforeDisplay: HospitalEvent::adminStatusLabel($beforeStatus),
                afterDisplay: HospitalEvent::adminStatusLabel($afterStatus),
            ),
            reason: $reason,
            metadata: ['bulk' => $bulk],
        );
    }

    public function recordAllowStatusUpdated(
        HospitalEvent $event,
        string $beforeStatus,
        string $afterStatus,
        ?string $reason = null,
        bool $bulk = false,
    ): void {
        $this->record(
            event: $event,
            action: OperationHistory::ACTION_STATE_UPDATED,
            source: 'staff.hospital_event.allow_status',
            changes: OperationHistoryChangeSetBuilder::single(
                key: 'allow_status',
                label: '검수상태',
                before: $beforeStatus,
                after: $afterStatus,
                beforeDisplay: HospitalEvent::allowStatusLabel($beforeStatus),
                afterDisplay: HospitalEvent::allowStatusLabel($afterStatus),
            ),
            reason: $reason,
            metadata: ['bulk' => $bulk],
        );
    }

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    private function snapshot(HospitalEvent $event): array
    {
        return [
            'hospital' => $this->item('병의원', (int) $event->hospital_id, $event->hospital?->name ?? (string) $event->hospital_id),
            'event_type' => $this->item('등록 유형', $event->event_type, $event->event_type === HospitalEvent::TYPE_TEXT ? '텍스트 등록' : '이미지 등록'),
            'is_male_targeted' => $this->item('남자성형 이벤트', (bool) $event->is_male_targeted, (bool) $event->is_male_targeted ? '선택' : '미선택'),
            'categories' => $this->item('카테고리', $this->categoryValue($event), $this->categoryDisplay($event)),
            'doctors' => $this->item('의료진 선택', $this->doctorValue($event), $this->doctorDisplay($event)),
            'name' => $this->item('이벤트명', $event->name, $event->name),
            'description' => $this->item('이벤트 설명', $event->description, $event->description),
            'event_period' => $this->item('기간', [
                'is_unlimited' => (bool) $event->is_event_period_unlimited,
                'start' => $event->event_start_at?->toDateString(),
                'end' => $event->event_end_at?->toDateString(),
            ], $this->periodLabel($event)),
            'is_vat_included' => $this->item('VAT', (bool) $event->is_vat_included, (bool) $event->is_vat_included ? 'VAT 포함' : 'VAT 비대상'),
            'normal_price' => $this->item('정상 가격', (int) $event->normal_price, $this->moneyLabel((int) $event->normal_price)),
            'event_price' => $this->item('이벤트 가격', (int) $event->event_price, $this->moneyLabel((int) $event->event_price)),
            'discount_rate' => $this->item('할인율', (int) $event->discount_rate, ((int) $event->discount_rate).'%'),
            'consultation_price' => $this->item('상담신청단가', (int) $event->consultation_price, $this->moneyLabel((int) $event->consultation_price)),
            'options' => $this->item('이벤트 옵션', $this->optionValue($event), $this->optionDisplay($event)),
            'procedure_targets' => $this->item('시술 대상', $event->procedure_targets ?? [], $this->lineList($event->procedure_targets ?? [])),
            'procedure_benefits' => $this->item('시술 장점', $event->procedure_benefits ?? [], $this->lineList($event->procedure_benefits ?? [])),
            'side_effect_notice' => $this->item('부작용안내', $event->side_effect_notice, $event->side_effect_notice),
            'thumbnail_image' => $this->item('썸네일', $event->thumbnailImage?->path, $this->mediaLabel($event->thumbnailImage?->path)),
            'event_page_image' => $this->item('이벤트 페이지', $event->eventPageImage?->path, $this->mediaLabel($event->eventPageImage?->path)),
        ];
    }

    /**
     * @return array{label:string,value:mixed,display:?string}
     */
    private function item(string $label, mixed $value, ?string $display): array
    {
        return compact('label', 'value', 'display');
    }

    /**
     * @param  array<string, array{label:string,value:mixed,display:?string}>  $before
     * @param  array<string, array{label:string,value:mixed,display:?string}>  $after
     * @return array<int, array<string, mixed>>
     */
    private function changes(array $before, array $after): array
    {
        $builder = OperationHistoryChangeSetBuilder::make();

        foreach ($after as $key => $afterItem) {
            $beforeItem = $before[$key] ?? $this->item($afterItem['label'], null, null);
            $builder->compare(
                key: $key,
                label: $afterItem['label'],
                before: $beforeItem['value'],
                after: $afterItem['value'],
                beforeDisplay: $beforeItem['display'],
                afterDisplay: $afterItem['display'],
            );
        }

        return $builder->toArray();
    }

    private function periodLabel(HospitalEvent $event): string
    {
        $startAt = $event->event_start_at?->format('y.m.d') ?? '-';
        if ((bool) $event->is_event_period_unlimited) {
            return "{$startAt} ~ 무기한";
        }

        return "{$startAt} ~ ".($event->event_end_at?->format('y.m.d') ?? '-');
    }

    private function moneyLabel(int $value): string
    {
        return number_format($value).'원';
    }

    private function mediaLabel(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        return basename($path);
    }

    /**
     * @return array<int, array{id:int,path:string,is_primary:bool}>
     */
    private function categoryValue(HospitalEvent $event): array
    {
        return $event->categories
            ->map(static fn ($category): array => [
                'id' => (int) $category->id,
                'path' => (string) ($category->full_path ?: $category->name),
                'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
            ])
            ->sortBy('path')
            ->values()
            ->all();
    }

    private function categoryDisplay(HospitalEvent $event): ?string
    {
        return $this->lineList(collect($this->categoryValue($event))
            ->map(static fn (array $category): string => ($category['is_primary'] ? '[대표] ' : '').$category['path'])
            ->all());
    }

    /**
     * @return array<int, array{id:int,name:string,is_career_visible:bool,is_activity_visible:bool}>
     */
    private function doctorValue(HospitalEvent $event): array
    {
        return $event->doctors
            ->map(static fn ($doctor): array => [
                'id' => (int) $doctor->id,
                'name' => (string) $doctor->name,
                'is_career_visible' => (bool) ($doctor->pivot?->is_career_visible ?? false),
                'is_activity_visible' => (bool) ($doctor->pivot?->is_activity_visible ?? false),
            ])
            ->sortBy('id')
            ->values()
            ->all();
    }

    private function doctorDisplay(HospitalEvent $event): ?string
    {
        return $this->lineList(collect($this->doctorValue($event))
            ->map(static function (array $doctor): string {
                $badges = [];
                if ($doctor['is_career_visible']) {
                    $badges[] = '경력';
                }
                if ($doctor['is_activity_visible']) {
                    $badges[] = '활동';
                }

                return $doctor['name'].($badges === [] ? '' : ' ('.implode(', ', $badges).')');
            })
            ->all());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function optionValue(HospitalEvent $event): array
    {
        return $event->options
            ->map(static fn ($option): array => [
                'name' => (string) $option->name,
                'session_count' => (int) $option->session_count,
                'normal_price' => (int) $option->normal_price,
                'event_price' => (int) $option->event_price,
                'discount_rate' => (int) $option->discount_rate,
            ])
            ->values()
            ->all();
    }

    private function optionDisplay(HospitalEvent $event): ?string
    {
        return $this->lineList(collect($this->optionValue($event))
            ->map(fn (array $option): string => sprintf(
                '%s / %d회 / 정가 %s / 할인가 %s / %d%%',
                $option['name'],
                $option['session_count'],
                $this->moneyLabel((int) $option['normal_price']),
                $this->moneyLabel((int) $option['event_price']),
                $option['discount_rate'],
            ))
            ->all());
    }

    /**
     * @param  array<int, mixed>  $items
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
     * @param  array<int, array<string, mixed>>  $changes
     */
    private function record(
        HospitalEvent $event,
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
            target: $event,
            action: $action,
            actor: $actor instanceof Model ? $actor : null,
            reason: $reason,
            metadata: ['source' => $source] + $metadata,
            changes: $changes,
        );
    }
}
