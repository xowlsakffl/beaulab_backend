<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalEvent\Models\HospitalEvent;

final class HospitalEventPayloadResolver
{
    /**
     * @return array<string, mixed>
     */
    public function normalizePersistPayload(array $payload, ?HospitalEvent $event = null): array
    {
        $normalPrice = $this->intValue($payload['normal_price'] ?? $event?->normal_price ?? 0);
        $eventPrice = $this->intValue($payload['event_price'] ?? $event?->event_price ?? 0);
        $discountRate = HospitalEvent::calculateDiscountRate($normalPrice, $eventPrice);

        if ($normalPrice > 0 && $eventPrice > $normalPrice) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '이벤트 가격은 정상 가격을 초과할 수 없습니다.');
        }

        if (HospitalEvent::exceedsMaxDiscountRate($normalPrice, $eventPrice)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '할인율은 49%를 초과할 수 없습니다.');
        }

        $baseConsultationPrice = HospitalEvent::consultationBasePrice($eventPrice);
        $consultationPrice = $this->intValue($payload['consultation_price'] ?? $baseConsultationPrice);

        if ($consultationPrice < $baseConsultationPrice) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '상담 신청 단가는 기준 단가보다 낮게 설정할 수 없습니다.');
        }

        $eventType = (string) ($payload['event_type'] ?? $event?->event_type ?? HospitalEvent::TYPE_IMAGE);
        $isUnlimited = $this->boolValue($payload['is_event_period_unlimited'] ?? $event?->is_event_period_unlimited ?? true);

        $data = [
            'hospital_id' => (int) ($payload['hospital_id'] ?? $event?->hospital_id),
            'event_type' => $eventType,
            'name' => $payload['name'] ?? $event?->name,
            'description' => $payload['description'] ?? $event?->description,
            'is_event_period_unlimited' => $isUnlimited,
            'event_start_at' => $payload['event_start_at'] ?? $event?->event_start_at,
            'event_end_at' => $isUnlimited ? null : ($payload['event_end_at'] ?? $event?->event_end_at),
            'normal_price' => $normalPrice,
            'event_price' => $eventPrice,
            'is_vat_included' => $this->boolValue($payload['is_vat_included'] ?? $event?->is_vat_included ?? true),
            'discount_rate' => $discountRate,
            'base_consultation_price' => $baseConsultationPrice,
            'consultation_price' => $consultationPrice,
            'has_options' => $this->boolValue($payload['has_options'] ?? $event?->has_options ?? false),
            'procedure_targets' => $eventType === HospitalEvent::TYPE_TEXT ? $this->normalizeTextItems($payload['procedure_targets'] ?? $event?->procedure_targets ?? []) : null,
            'procedure_benefits' => $eventType === HospitalEvent::TYPE_TEXT ? $this->normalizeTextItems($payload['procedure_benefits'] ?? $event?->procedure_benefits ?? []) : null,
            'side_effect_notice' => $payload['side_effect_notice'] ?? $event?->side_effect_notice,
            'allow_status' => $payload['allow_status'] ?? $event?->allow_status ?? HospitalEvent::ALLOW_PENDING,
            'status' => $payload['status'] ?? $event?->status ?? HospitalEvent::STATUS_INACTIVE,
        ];

        if ($eventType === HospitalEvent::TYPE_TEXT) {
            $this->assertTextItems($data['procedure_targets'], '시술 대상');
            $this->assertTextItems($data['procedure_benefits'], '시술 장점');
        }

        return $data;
    }

    /**
     * @return array{usage:string,payload:array<int, array{is_primary:bool}>}
     */
    public function resolveCategorySyncPayload(array $categoryIds, int $primaryCategoryId): array
    {
        $categoryIds = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->unique()
            ->values()
            ->all();

        if ($categoryIds === [] || count($categoryIds) > HospitalEvent::MAX_CATEGORY_COUNT) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '카테고리는 1개 이상 3개 이하로 선택해 주세요.');
        }

        if (! in_array($primaryCategoryId, $categoryIds, true)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '대표 카테고리는 선택한 카테고리 안에서 지정해 주세요.');
        }

        $categories = Category::query()
            ->whereIn('id', $categoryIds)
            ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->where('status', Category::STATUS_ACTIVE)
            ->get(['id', 'full_path', 'name']);

        if ($categories->count() !== count($categoryIds)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '선택한 카테고리 값이 올바르지 않습니다.');
        }

        $hasActiveChildren = Category::query()
            ->whereIn('parent_id', $categoryIds)
            ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->where('status', Category::STATUS_ACTIVE)
            ->exists();

        if ($hasActiveChildren) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '이벤트 카테고리는 소분류만 선택할 수 있습니다.');
        }

        $usage = $this->resolveSingleCategoryUsage($categories);

        $payload = collect($categoryIds)
            ->mapWithKeys(static fn (int $categoryId): array => [
                $categoryId => ['is_primary' => $categoryId === $primaryCategoryId],
            ])
            ->all();

        return [
            'usage' => $usage,
            'payload' => $payload,
        ];
    }

    /**
     * @return array<int, array{hospital_doctor_id:int,sort_order:int,is_career_visible:bool,is_activity_visible:bool}>
     */
    public function resolveDoctorAssignments(array $payload, int $hospitalId): array
    {
        $assignments = $payload['doctor_assignments'] ?? [];

        if ($assignments === [] && isset($payload['doctor_ids']) && is_array($payload['doctor_ids'])) {
            $assignments = collect($payload['doctor_ids'])
                ->map(static fn (int|string $doctorId, int $index): array => [
                    'id' => (int) $doctorId,
                    'sort_order' => $index,
                    'is_career_visible' => true,
                    'is_activity_visible' => false,
                ])
                ->all();
        }

        $normalized = collect($assignments)
            ->map(static function (mixed $assignment, int $index): array {
                if (! is_array($assignment)) {
                    $assignment = ['id' => $assignment];
                }

                return [
                    'hospital_doctor_id' => (int) ($assignment['hospital_doctor_id'] ?? $assignment['id'] ?? 0),
                    'sort_order' => (int) ($assignment['sort_order'] ?? $index),
                    'is_career_visible' => (bool) ($assignment['is_career_visible'] ?? true),
                    'is_activity_visible' => (bool) ($assignment['is_activity_visible'] ?? false),
                ];
            })
            ->filter(static fn (array $assignment): bool => $assignment['hospital_doctor_id'] > 0)
            ->unique('hospital_doctor_id')
            ->values()
            ->all();

        if (count($normalized) > HospitalEvent::MAX_DOCTOR_COUNT) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '의료진은 최대 3명까지 선택할 수 있습니다.');
        }

        $doctorIds = collect($normalized)->pluck('hospital_doctor_id')->all();
        if ($doctorIds === []) {
            return [];
        }

        $validDoctorCount = HospitalDoctor::query()
            ->whereIn('id', $doctorIds)
            ->where('hospital_id', $hospitalId)
            ->count();

        if ($validDoctorCount !== count($doctorIds)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '선택한 의료진은 해당 병의원 소속이어야 합니다.');
        }

        return $normalized;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function resolveOptions(array $payload, string $categoryUsage): array
    {
        $hasOptions = $this->boolValue($payload['has_options'] ?? false);
        if (! $hasOptions) {
            return [];
        }

        if ($categoryUsage !== CategoryUsage::USAGE_HOSPITAL_EVENT_TREATMENT) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '이벤트 옵션은 시술 카테고리 이벤트에서만 사용할 수 있습니다.');
        }

        $options = $payload['options'] ?? [];
        if (! is_array($options) || $options === []) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '이벤트 옵션을 1개 이상 입력해 주세요.');
        }

        $normalized = collect($options)
            ->map(function (mixed $option, int $index): array {
                if (! is_array($option)) {
                    $option = [];
                }

                $normalPrice = $this->intValue($option['normal_price'] ?? 0);
                $eventPrice = $this->intValue($option['event_price'] ?? 0);

                if ($normalPrice > 0 && $eventPrice > $normalPrice) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '옵션 할인가는 옵션 정가를 초과할 수 없습니다.');
                }

                $discountRate = $normalPrice > 0 && $eventPrice > 0
                    ? floor((1 - ($eventPrice / $normalPrice)) * 100)
                    : 0;

                return [
                    'sort_order' => (int) ($option['sort_order'] ?? $index),
                    'name' => (string) ($option['name'] ?? ''),
                    'session_count' => max(1, (int) ($option['session_count'] ?? 1)),
                    'normal_price' => $normalPrice,
                    'event_price' => $eventPrice,
                    'discount_rate' => max(0, (int) $discountRate),
                ];
            })
            ->filter(static fn (array $option): bool => trim((string) $option['name']) !== '')
            ->values()
            ->all();

        if ($normalized === []) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '이벤트 옵션을 1개 이상 입력해 주세요.');
        }

        return $normalized;
    }

    public function syncDoctorAssignments(HospitalEvent $event, array $assignments): void
    {
        $event->doctorAssignments()->delete();

        foreach ($assignments as $assignment) {
            $event->doctorAssignments()->create($assignment);
        }
    }

    public function syncOptions(HospitalEvent $event, array $options): void
    {
        $event->options()->delete();

        foreach ($options as $option) {
            $event->options()->create($option);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Category>  $categories
     */
    private function resolveSingleCategoryUsage($categories): string
    {
        $pathsByUsage = CategoryUsage::activeCategoryFullPathsByUsage([
            CategoryUsage::USAGE_HOSPITAL_EVENT_SURGERY,
            CategoryUsage::USAGE_HOSPITAL_EVENT_TREATMENT,
        ]);

        $matchedUsages = [];
        foreach ($categories as $category) {
            $categoryPath = trim((string) ($category->full_path ?: $category->name));
            $matchedForCategory = [];

            foreach ($pathsByUsage as $usage => $rootPaths) {
                foreach ($rootPaths as $rootPath) {
                    if ($categoryPath === $rootPath || str_starts_with($categoryPath, $rootPath.' > ')) {
                        $matchedForCategory[] = $usage;
                        break;
                    }
                }
            }

            if (count($matchedForCategory) !== 1) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '이벤트 카테고리는 성형 또는 시술 카테고리 중 하나에만 속해야 합니다.');
            }

            $matchedUsages[] = $matchedForCategory[0];
        }

        $uniqueUsages = array_values(array_unique($matchedUsages));
        if (count($uniqueUsages) !== 1) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '성형 카테고리와 시술 카테고리는 섞어서 선택할 수 없습니다.');
        }

        return $uniqueUsages[0];
    }

    /**
     * @return array<int, string>
     */
    private function normalizeTextItems(mixed $items): array
    {
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $items = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [$items];
        }

        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(static fn (mixed $item): string => trim((string) $item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->take(HospitalEvent::MAX_TEXT_ITEMS)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $items
     */
    private function assertTextItems(array $items, string $label): void
    {
        if (count($items) < HospitalEvent::MIN_TEXT_ITEMS) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, "{$label}은 2개 이상 입력해 주세요.");
        }
    }

    private function intValue(mixed $value): int
    {
        if (is_string($value)) {
            $value = str_replace(',', '', $value);
        }

        return max(0, (int) $value);
    }

    private function boolValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }
}
