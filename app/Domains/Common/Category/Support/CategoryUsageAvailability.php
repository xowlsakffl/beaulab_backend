<?php

namespace App\Domains\Common\Category\Support;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use Illuminate\Support\Collection;

final class CategoryUsageAvailability
{
    public const OPTIONS = [
        'HOSPITAL_DOCTOR_SUBJECT' => ['label' => '병의원 진료과목 연동', 'groups' => ['SURGERY', 'TREATMENT']],
        'HOSPITAL_SUBJECT' => ['label' => '병의원 · 진료과목', 'groups' => ['SURGERY', 'TREATMENT']],
        'DOCTOR_SUBJECT' => ['label' => '의료진 · 진료분야', 'groups' => ['SURGERY', 'TREATMENT']],
        'HOSPITAL_REVIEW_SURGERY' => ['label' => '후기 · 성형', 'groups' => ['SURGERY']],
        'HOSPITAL_REVIEW_TREATMENT' => ['label' => '후기 · 쁘띠', 'groups' => ['TREATMENT']],
        'HOSPITAL_EVENT_SURGERY' => ['label' => '이벤트 · 성형', 'groups' => ['SURGERY']],
        'HOSPITAL_EVENT_TREATMENT' => ['label' => '이벤트 · 쁘띠', 'groups' => ['TREATMENT']],
        'HOSPITAL_EVENT_PROMOTION' => ['label' => '이벤트 · 기획전', 'groups' => ['PROMOTION']],
        'HOSPITAL_VIDEO_CATEGORY' => ['label' => '동영상', 'groups' => ['SURGERY', 'TREATMENT']],
        'HOSPITAL_EVENT_AD_SURGERY' => ['label' => '이벤트 광고 · 성형', 'groups' => ['SURGERY']],
        'HOSPITAL_EVENT_AD_TREATMENT' => ['label' => '이벤트 광고 · 쁘띠', 'groups' => ['TREATMENT']],
    ];

    public function resolve(Collection $categories): array
    {
        $result = [];
        foreach ($categories->sortBy('depth') as $category) {
            $parent = $category->parent_id ? ($result[$category->parent_id] ?? null) : null;
            $active = $category->status === Category::STATUS_ACTIVE && (! $category->parent_id || ($parent['active'] ?? false));
            $menu = $category->is_menu_visible && (! $category->parent_id || ($parent['menu'] ?? false));
            $usages = [];
            foreach (self::OPTIONS as $usage => $option) {
                $eligible = $category->domain === CategoryUsage::domainFor($usage) && in_array($category->group_code, $option['groups'], true);
                $usages[$usage] = [
                    'display_name' => null, 'name' => $category->name, 'full_path' => $category->full_path,
                    'enabled' => $eligible && $active, 'visible' => $eligible && $menu,
                    'own_enabled' => $category->status === Category::STATUS_ACTIVE,
                    'own_visible' => (bool) $category->is_menu_visible,
                    'parent_enabled' => ! $category->parent_id || ($parent['active'] ?? false),
                    'parent_visible' => ! $category->parent_id || ($parent['menu'] ?? false),
                    'eligible' => $eligible, 'sort_order' => $category->sort_order, 'inherited' => false,
                ];
            }
            $result[$category->id] = ['active' => $active, 'menu' => $menu, 'group_code' => $category->group_code, 'usages' => $usages];
        }

        return $result;
    }

    public function ids(string $usage, bool $menuOnly = false): array
    {
        return $this->idsForUsages([$usage], $menuOnly);
    }

    public function presentation(Category $category, string $usage): array
    {
        $resolved = $this->current(CategoryUsage::domainFor($usage))[$category->id] ?? null;
        if (in_array($usage, ['HOSPITAL_EVENT', 'HOSPITAL_REVIEW', 'HOSPITAL_EVENT_AD'], true)) {
            $usage .= '_'.($resolved['group_code'] ?? $category->group_code);
        }

        return $resolved['usages'][$usage]
            ?? ['name' => (string) $category->name, 'full_path' => (string) ($category->full_path ?: $category->name), 'enabled' => false, 'visible' => false, 'sort_order' => $category->sort_order];
    }

    public function searchIds(string $usage, string $query): array
    {
        return array_keys(array_filter($this->current(CategoryUsage::domainFor($usage)),
            fn ($row) => mb_stripos($row['usages'][$usage]['full_path'] ?? '', $query) !== false));
    }

    public function activeIdsForDomain(string $domain, bool $menuOnly = false): array
    {
        return array_keys(array_filter($this->current($domain), fn ($row) => $row[$menuOnly ? 'menu' : 'active']));
    }

    public function idsForUsages(array $usages, bool $menuOnly = false): array
    {
        $ids = [];
        foreach ($usages as $usage) {
            foreach ($this->current(CategoryUsage::domainFor($usage)) as $id => $row) {
                if ($row['usages'][$usage][$menuOnly ? 'visible' : 'enabled'] ?? false) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    private function current(string $domain): array
    {
        $key = self::class.'.'.$domain;
        $attributes = request()->attributes;
        if (! $attributes->has($key)) {
            $attributes->set($key, $this->resolve(Category::query()->domain($domain)->get()));
        }

        return $attributes->get($key);
    }

    public static function forgetRequestCache(): void
    {
        request()->attributes->remove(HospitalDepartment::class);
        foreach (Category::domains() as $domain) {
            request()->attributes->remove(self::class.'.'.$domain);
        }
    }
}
