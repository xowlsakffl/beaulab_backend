<?php

namespace App\Domains\Beauty\Actions\Staff;

use App\Domains\Beauty\Models\Beauty;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\Common\OperationHistory\Support\OperationHistoryDisplayValue;
use Illuminate\Database\Eloquent\Model;

final class BeautyUpdateHistoryRecordAction
{
    public function __construct(
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    public function capture(Beauty $beauty): array
    {
        $beauty->loadMissing(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia', 'categories']);

        return [
            'name' => $this->item('업체명', $beauty->name, $beauty->name),
            'description' => $this->item('소개', $beauty->description, $beauty->description),
            'address' => $this->item('주소', $beauty->address, $beauty->address),
            'address_detail' => $this->item('상세주소', $beauty->address_detail, $beauty->address_detail),
            'latitude' => $this->item('위도', $beauty->latitude, $beauty->latitude),
            'longitude' => $this->item('경도', $beauty->longitude, $beauty->longitude),
            'tel' => $this->item('전화번호', $beauty->tel, $beauty->tel),
            'email' => $this->item('이메일', $beauty->email, $beauty->email),
            'consulting_hours' => $this->item('상담시간', $beauty->consulting_hours, $beauty->consulting_hours),
            'direction' => $this->item('오시는길', $beauty->direction, $beauty->direction),
            'allow_status' => $this->item('검수상태', $beauty->allow_status, $beauty->allow_status),
            'status' => $this->item('업체상태', $beauty->status, $beauty->status),
            'categories' => $this->item('카테고리', $this->categoryValue($beauty), $this->categoryDisplay($beauty)),
            'business_registration' => $this->item('사업자등록정보', $this->businessValue($beauty), $this->businessDisplay($beauty)),
            'logo' => $this->item('로고', $beauty->logoMedia?->path, OperationHistoryDisplayValue::fileName($beauty->logoMedia?->path)),
            'gallery' => $this->item('업체 이미지', $this->galleryValue($beauty), $this->galleryDisplay($beauty)),
        ];
    }

    public function recordCreated(Beauty $beauty): void
    {
        $this->record($beauty, OperationHistory::ACTION_CREATED, 'staff.beauty.create', []);
    }

    /**
     * @param  array<string, array{label:string,value:mixed,display:?string}>  $before
     */
    public function recordUpdated(Beauty $beauty, array $before): void
    {
        foreach (OperationHistoryChangeSetBuilder::groupedFromSnapshots($before, $this->capture($beauty), ['allow_status', 'status']) as $action => $changes) {
            $this->record($beauty, $action, 'staff.beauty.update', $changes);
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
    private function categoryValue(Beauty $beauty): array
    {
        return $beauty->categories
            ->map(static fn ($category): array => [
                'id' => (int) $category->id,
                'path' => (string) ($category->full_path ?: $category->name),
                'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
            ])
            ->sortBy('path')
            ->values()
            ->all();
    }

    private function categoryDisplay(Beauty $beauty): ?string
    {
        return OperationHistoryDisplayValue::lines(collect($this->categoryValue($beauty))
            ->map(static fn (array $category): string => ($category['is_primary'] ? '[대표] ' : '').$category['path'])
            ->all());
    }

    /**
     * @return array<string, mixed>|null
     */
    private function businessValue(Beauty $beauty): ?array
    {
        $business = $beauty->businessRegistration;
        if (! $business) {
            return null;
        }

        return [
            'business_number' => $business->business_number,
            'company_name' => $business->company_name,
            'ceo_name' => $business->ceo_name,
            'business_type' => $business->business_type,
            'business_item' => $business->business_item,
            'business_address' => $business->business_address,
            'business_address_detail' => $business->business_address_detail,
            'issued_at' => $business->issued_at?->toDateString(),
            'status' => $business->status,
            'certificate' => $business->certificateMedia?->path,
        ];
    }

    private function businessDisplay(Beauty $beauty): ?string
    {
        $business = $this->businessValue($beauty);
        if ($business === null) {
            return null;
        }

        return OperationHistoryDisplayValue::lines([
            $business['company_name'] ?? null,
            $business['business_number'] ?? null,
            $business['ceo_name'] ?? null,
        ]);
    }

    /**
     * @return array<int, array{id:int,path:string}>
     */
    private function galleryValue(Beauty $beauty): array
    {
        return $beauty->galleryMedia
            ->map(static fn ($media): array => [
                'id' => (int) $media->id,
                'path' => (string) $media->path,
            ])
            ->values()
            ->all();
    }

    private function galleryDisplay(Beauty $beauty): ?string
    {
        return OperationHistoryDisplayValue::lines(collect($this->galleryValue($beauty))
            ->map(static fn (array $media): string => OperationHistoryDisplayValue::fileName($media['path']))
            ->all());
    }

    /**
     * @param  array<int, array<string, mixed>>  $changes
     */
    private function record(Beauty $beauty, string $action, string $source, array $changes): void
    {
        if ($changes === [] && $action !== OperationHistory::ACTION_CREATED) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $beauty,
            action: $action,
            actor: $actor instanceof Model ? $actor : null,
            reason: null,
            metadata: ['source' => $source],
            changes: $changes,
        );
    }
}
