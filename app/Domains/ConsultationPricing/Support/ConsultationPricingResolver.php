<?php

namespace App\Domains\ConsultationPricing\Support;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\ConsultationPricing\Queries\ConsultationPricingQuery;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Carbon\CarbonInterface;

final class ConsultationPricingResolver
{
    public const USAGES = ['SURGERY' => CategoryUsage::USAGE_HOSPITAL_EVENT_SURGERY, 'TREATMENT' => CategoryUsage::USAGE_HOSPITAL_EVENT_TREATMENT, 'PROMOTION' => CategoryUsage::USAGE_HOSPITAL_EVENT_PROMOTION];

    public function __construct(private readonly ConsultationPricingQuery $query) {}

    public static function defaultRanges(): array
    {
        return array_map(fn ($upper, $price) => ['upper' => $upper, 'price' => $price],
            [50000, 100000, 200000, 300000, 500000, 800000, 1000000, 1500000, 2500000, 3000000, 4000000, null],
            [10000, 12500, 15000, 17500, 20000, 22500, 25000, 27500, 30000, 32500, 37500, 40000]);
    }

    public function categories(string $division)
    {
        $query = Category::query()->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)->where('group_code', $division);

        return $query->whereNull('parent_id')->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'full_path']);
    }

    public function table(string $key, ?CarbonInterface $at = null): array
    {
        $at ??= now();
        $version = $this->query->current($key, $at);
        if ($version?->mode === 'DEFAULT') {
            $base = $this->table(substr($key, 0, strrpos($key, ':')).':0', $at);

            return [...$base, 'mode' => 'DEFAULT'];
        }
        if ($version) {
            return ['mode' => $version->mode, 'ranges' => $version->ranges, 'fixed_price' => $version->fixed_price, 'version_id' => $version->id, 'effective_at' => $version->effective_at->toIso8601String()];
        }
        if (str_starts_with($key, 'EVENT:')) {
            if (! str_ends_with($key, ':0')) {
                return [...$this->table(substr($key, 0, strrpos($key, ':')).':0', $at), 'mode' => 'DEFAULT'];
            }

            return ['mode' => 'CUSTOM', 'ranges' => self::defaultRanges(), 'fixed_price' => null, 'version_id' => null, 'effective_at' => null];
        }

        return ['mode' => 'CUSTOM', 'ranges' => null, 'fixed_price' => null, 'version_id' => null, 'effective_at' => null];
    }

    public function fixed(string $type, ?CarbonInterface $at = null): array
    {
        $table = $this->table($type, $at);
        if ($table['fixed_price'] === null) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '상담신청 단가가 아직 설정되지 않았습니다. 관리자에게 문의해 주세요.');
        }

        return ['price' => (int) $table['fixed_price'], 'version_id' => $table['version_id']];
    }

    public function event(int $categoryId, int $price, ?CarbonInterface $at = null): array
    {
        $categories = Category::where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)->get(['id', 'parent_id', 'depth', 'group_code', 'name', 'domain', 'full_path']);
        $roots = \App\Domains\Common\Category\Support\CategoryHierarchy::rootIds($categories);
        $root = $categories->firstWhere('id', $roots[$categoryId] ?? null);
        if (! $root || ! array_key_exists($root->group_code, self::USAGES)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '단가 계산에 사용할 대표 카테고리를 확인해 주세요.');
        }
        $division = $root->group_code;
        $table = $this->table("EVENT:{$division}:{$root->id}", $at);
        foreach ($table['ranges'] as $range) {
            if ($range['upper'] === null || $price <= $range['upper']) {
                return ['price' => $range['price'], 'version_id' => $table['version_id'], 'mode' => $table['mode'], 'category_name' => $root->displayNameFor('HOSPITAL_EVENT')];
            }
        }
        throw new CustomException(ErrorCode::INVALID_REQUEST, '적용 가능한 상담신청 단가가 없습니다.');
    }

    public function forEvent(HospitalEvent $event): array
    {
        $primary = $event->categories()->wherePivot('is_primary', true)->value('categories.id');

        return $this->event((int) $primary, (int) $event->event_price);
    }
}
