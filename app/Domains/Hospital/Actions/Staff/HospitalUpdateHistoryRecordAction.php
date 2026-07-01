<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Database\Eloquent\Model;

final class HospitalUpdateHistoryRecordAction
{
    public function __construct(
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    public function capture(Hospital $hospital): array
    {
        $hospital->loadMissing([
            'businessRegistration.certificateMedia',
            'logoMedia',
            'galleryMedia',
            'categories',
            'features',
        ]);

        return $this->snapshot($hospital);
    }

    public function recordCreated(Hospital $hospital): void
    {
        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $hospital,
            action: OperationHistory::ACTION_CREATED,
            actor: $actor instanceof Model ? $actor : null,
            reason: null,
            metadata: [
                'source' => 'staff.hospital.create',
            ],
        );
    }

    /**
     * @param  array<string, array{label:string,value:mixed,display:?string}>  $before
     */
    public function recordUpdated(Hospital $hospital, array $before): void
    {
        $hospital->load([
            'businessRegistration.certificateMedia',
            'logoMedia',
            'galleryMedia',
            'categories',
            'features',
        ]);

        $changes = $this->changes($before, $this->snapshot($hospital));
        if ($changes === []) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $hospital,
            action: OperationHistory::ACTION_UPDATED,
            actor: $actor instanceof Model ? $actor : null,
            reason: null,
            metadata: [
                'source' => 'staff.hospital.update',
            ],
            changes: $changes,
        );
    }

    public function recordStatusUpdated(
        Hospital $hospital,
        string $beforeStatus,
        string $afterStatus,
        ?string $reason = null,
    ): void {
        $changes = OperationHistoryChangeSetBuilder::single(
            key: 'status',
            label: '병의원상태',
            before: $beforeStatus,
            after: $afterStatus,
            beforeDisplay: Hospital::statusLabel($beforeStatus),
            afterDisplay: Hospital::statusLabel($afterStatus),
        );

        if ($changes === []) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $hospital,
            action: OperationHistory::ACTION_STATE_UPDATED,
            actor: $actor instanceof Model ? $actor : null,
            reason: $reason,
            metadata: [
                'source' => 'staff.hospital.status',
            ],
            changes: $changes,
        );
    }

    public function recordAllowStatusUpdated(
        Hospital $hospital,
        string $beforeStatus,
        string $afterStatus,
        ?string $reason = null,
        bool $bulk = false,
    ): void {
        $changes = OperationHistoryChangeSetBuilder::single(
            key: 'allow_status',
            label: '검수상태',
            before: $beforeStatus,
            after: $afterStatus,
            beforeDisplay: Hospital::allowStatusLabel($beforeStatus),
            afterDisplay: Hospital::allowStatusLabel($afterStatus),
        );

        if ($changes === []) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $hospital,
            action: OperationHistory::ACTION_STATE_UPDATED,
            actor: $actor instanceof Model ? $actor : null,
            reason: $reason,
            metadata: [
                'source' => 'staff.hospital.allow_status',
                'bulk' => $bulk,
            ],
            changes: $changes,
        );
    }

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    private function snapshot(Hospital $hospital): array
    {
        $businessRegistration = $hospital->businessRegistration;

        return [
            'name' => $this->item('병의원명', $hospital->name, $hospital->name),
            'department' => $this->item('분과', $hospital->department, $hospital->departmentLabel()),
            'description' => $this->item('병의원소개', $hospital->description, $hospital->description),
            'youtube_link' => $this->item('유튜브 링크', $hospital->youtube_link, $hospital->youtube_link),
            'address' => $this->item('병의원주소', [
                'address' => $hospital->address,
                'address_detail' => $hospital->address_detail,
            ], $this->addressLabel($hospital->address, $hospital->address_detail)),
            'tel' => $this->item('전화번호', $hospital->tel, $hospital->tel),
            'ad_reception_phones' => $this->item('광고 안내 수신 전화번호', [
                $hospital->ad_reception_phone_1,
                $hospital->ad_reception_phone_2,
                $hospital->ad_reception_phone_3,
            ], $this->lineList([
                $hospital->ad_reception_phone_1 ? '[필수] '.$hospital->ad_reception_phone_1 : null,
                $hospital->ad_reception_phone_2 ? '[선택] '.$hospital->ad_reception_phone_2 : null,
                $hospital->ad_reception_phone_3 ? '[선택] '.$hospital->ad_reception_phone_3 : null,
            ])),
            'email' => $this->item('이메일', $hospital->email, $hospital->email),
            'consulting_hours' => $this->item('상담시간', $hospital->consulting_hours, $hospital->consulting_hours),
            'operation_hours' => $this->item('진료시간', $hospital->operation_hours, $this->operationHoursLabel($hospital->operation_hours)),
            'direction' => $this->item('오시는 길', $hospital->direction, $hospital->direction),
            'categories' => $this->item('진료과목', $this->categoryValue($hospital), $this->categoryDisplay($hospital)),
            'features' => $this->item('병원정보', $this->featureValue($hospital), $this->featureDisplay($hospital)),
            'logo' => $this->item('로고 이미지', $hospital->logoMedia?->path, $this->mediaLabel($hospital->logoMedia?->path)),
            'gallery' => $this->item('병의원 이미지', $this->galleryValue($hospital), $this->galleryDisplay($hospital)),
            'business_number' => $this->item('사업자등록번호', $businessRegistration?->business_number, $businessRegistration?->business_number),
            'company_name' => $this->item('상호', $businessRegistration?->company_name, $businessRegistration?->company_name),
            'ceo_name' => $this->item('대표자', $businessRegistration?->ceo_name, $businessRegistration?->ceo_name),
            'business_type' => $this->item('업태', $businessRegistration?->business_type, $businessRegistration?->business_type),
            'business_item' => $this->item('종목', $businessRegistration?->business_item, $businessRegistration?->business_item),
            'tax_invoice_email' => $this->item('세금계산서 이메일', $businessRegistration?->tax_invoice_email, $businessRegistration?->tax_invoice_email),
            'settlement_account' => $this->item('정산계좌번호', [
                'bank' => $businessRegistration?->settlement_bank_name,
                'account' => $businessRegistration?->settlement_account_number,
                'holder' => $businessRegistration?->settlement_account_holder,
            ], $this->settlementAccountLabel($businessRegistration)),
            'business_registration_file' => $this->item(
                '사업자등록증',
                $businessRegistration?->certificateMedia?->path,
                $this->mediaLabel($businessRegistration?->certificateMedia?->path),
            ),
            'allow_status' => $this->item('검수상태', $hospital->allow_status, Hospital::allowStatusLabel((string) $hospital->allow_status)),
            'status' => $this->item('병의원상태', $hospital->status, Hospital::statusLabel((string) $hospital->status)),
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

    private function addressLabel(?string $address, ?string $detail): ?string
    {
        return $this->lineList([$address, $detail]);
    }

    private function settlementAccountLabel(mixed $businessRegistration): ?string
    {
        if ($businessRegistration === null) {
            return null;
        }

        return $this->lineList([
            $businessRegistration->settlement_bank_name,
            $businessRegistration->settlement_account_number,
            $businessRegistration->settlement_account_holder,
        ]);
    }

    private function operationHoursLabel(mixed $operationHours): ?string
    {
        if (! is_array($operationHours) || $operationHours === []) {
            return null;
        }

        $dayLabels = [
            'mon' => '월',
            'tue' => '화',
            'wed' => '수',
            'thu' => '목',
            'fri' => '금',
            'sat' => '토',
            'sun' => '일',
        ];

        $lines = [];
        foreach ($dayLabels as $key => $label) {
            $item = $operationHours[$key] ?? null;
            if (! is_array($item)) {
                continue;
            }

            if ($this->isOperationDayClosed($item['is_closed'] ?? false)) {
                $lines[] = "{$label} 진료안함";

                continue;
            }

            $start = trim((string) ($item['start'] ?? ''));
            $end = trim((string) ($item['end'] ?? ''));
            $lines[] = sprintf('%s %s ~ %s', $label, $start !== '' ? $start : '-', $end !== '' ? $end : '-');
        }

        return $this->lineList($lines);
    }

    private function isOperationDayClosed(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'TRUE'], true);
    }

    private function mediaLabel(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        return basename($path);
    }

    /**
     * @return array<int, array{id:int,path:string}>
     */
    private function categoryValue(Hospital $hospital): array
    {
        return $hospital->categories
            ->map(static fn ($category): array => [
                'id' => (int) $category->id,
                'path' => (string) ($category->full_path ?: $category->name),
            ])
            ->sortBy('path')
            ->values()
            ->all();
    }

    private function categoryDisplay(Hospital $hospital): ?string
    {
        return $this->lineList(collect($this->categoryValue($hospital))
            ->pluck('path')
            ->all());
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function featureValue(Hospital $hospital): array
    {
        return $hospital->features
            ->map(static fn ($feature): array => [
                'id' => (int) $feature->id,
                'name' => (string) $feature->name,
            ])
            ->sortBy('name')
            ->values()
            ->all();
    }

    private function featureDisplay(Hospital $hospital): ?string
    {
        return $this->lineList(collect($this->featureValue($hospital))
            ->pluck('name')
            ->all());
    }

    /**
     * @return array<int, array{id:int,path:string,is_primary:bool,sort_order:int}>
     */
    private function galleryValue(Hospital $hospital): array
    {
        return $hospital->galleryMedia
            ->map(static fn ($media): array => [
                'id' => (int) $media->id,
                'path' => (string) $media->path,
                'is_primary' => (bool) $media->is_primary,
                'sort_order' => (int) $media->sort_order,
            ])
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    private function galleryDisplay(Hospital $hospital): ?string
    {
        return $this->lineList(collect($this->galleryValue($hospital))
            ->map(fn (array $media): string => ($media['is_primary'] ? '[대표] ' : '').$this->mediaLabel($media['path']))
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
}
