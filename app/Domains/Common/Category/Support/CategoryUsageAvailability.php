<?php

namespace App\Domains\Common\Category\Support;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use Illuminate\Support\Collection;

final class CategoryUsageAvailability
{
    public const OPTIONS = [
        'HOSPITAL_DOCTOR_SUBJECT' => ['label' => '기존 진료과목 연동', 'groups' => ['SURGERY', 'TREATMENT']],
        'HOSPITAL_SUBJECT' => ['label' => '병의원 · 진료과목', 'groups' => ['SURGERY', 'TREATMENT']],
        'DOCTOR_SUBJECT' => ['label' => '의료진 · 진료분야', 'groups' => ['SURGERY', 'TREATMENT']],
        'HOSPITAL_REVIEW_SURGERY' => ['label' => '후기 · 성형', 'groups' => ['SURGERY']],
        'HOSPITAL_REVIEW_TREATMENT' => ['label' => '후기 · 쁘띠', 'groups' => ['TREATMENT']],
        'HOSPITAL_EVENT_SURGERY' => ['label' => '이벤트 · 성형', 'groups' => ['SURGERY']],
        'HOSPITAL_EVENT_TREATMENT' => ['label' => '이벤트 · 쁘띠', 'groups' => ['TREATMENT']],
        'HOSPITAL_EVENT_PROMOTION' => ['label' => '이벤트 · 기획전', 'groups' => ['PROMOTION']],
        'HOSPITAL_VIDEO_CATEGORY' => ['label' => '동영상', 'groups' => ['SURGERY', 'TREATMENT']],
        'HOSPITAL_EVENT_AD_SURGERY' => ['label' => '광고 · 성형', 'groups' => ['SURGERY']],
        'HOSPITAL_EVENT_AD_TREATMENT' => ['label' => '광고 · 쁘띠', 'groups' => ['TREATMENT']],
    ];

    public function resolve(Collection $categories): array
    {
        $result = [];
        foreach ($categories->sortBy('depth') as $category) {
            $parent = $category->parent_id ? ($result[$category->parent_id] ?? null) : null;
            $active = $category->status === Category::STATUS_ACTIVE && (! $category->parent_id || ($parent['active'] ?? false));
            $menu = $active && $category->is_menu_visible && (! $category->parent_id || ($parent['menu'] ?? false));
            $usages = [];
            foreach (self::OPTIONS as $usage => $option) {
                $row = $category->usages->firstWhere('usage', $usage);
                if ($row === null && in_array($usage, ['HOSPITAL_SUBJECT', 'DOCTOR_SUBJECT'], true)) {
                    $row = $category->usages->firstWhere('usage', 'HOSPITAL_DOCTOR_SUBJECT');
                }
                $eligible = $category->domain === Category::DOMAIN_HOSPITAL_MEDICAL && in_array($category->group_code, $option['groups'], true);
                $parentUsed = ! $category->parent_id || ($parent['usages'][$usage]['enabled'] ?? false);
                $parentVisible = ! $category->parent_id || ($parent['usages'][$usage]['visible'] ?? false);
                $ownUsed = $row ? $row->status === CategoryUsage::STATUS_ACTIVE : (bool) $category->parent_id;
                $ownVisible = $row?->is_menu_visible ?? true;
                $enabled = $eligible && $active && $parentUsed && $ownUsed;
                $visible = $enabled && $menu && $ownVisible && $parentVisible;
                $name = $row?->display_name ?: $category->name;
                $path = $parent ? $parent['usages'][$usage]['full_path'].' > '.$name : $name;
                $usages[$usage] = [
                    'display_name' => $row?->display_name, 'name' => $name, 'full_path' => $path,
                    'enabled' => $enabled, 'visible' => $visible,
                    'own_enabled' => $ownUsed, 'own_visible' => $ownVisible,
                    'parent_enabled' => $parentUsed, 'eligible' => $eligible,
                    'parent_visible' => $parentVisible,
                    'sort_order' => $row?->sort_order ?? $category->sort_order,
                    'inherited' => $row === null && $category->parent_id !== null,
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
        $resolved = $this->current(Category::DOMAIN_HOSPITAL_MEDICAL)[$category->id] ?? null;
        // 콘텐츠 DTO가 그룹 컬럼을 조회하지 않아도 같은 업무의 표시명을 사용한다.
        if (in_array($usage, ['HOSPITAL_EVENT', 'HOSPITAL_REVIEW', 'HOSPITAL_EVENT_AD'], true)) {
            $usage .= '_'.($resolved['group_code'] ?? $category->group_code);
        }

        return $resolved['usages'][$usage]
            ?? ['name' => (string) $category->name, 'full_path' => (string) ($category->full_path ?: $category->name)];
    }

    public function searchIds(string $usage, string $query): array
    {
        return array_keys(array_filter($this->current(Category::DOMAIN_HOSPITAL_MEDICAL),
            fn ($row) => mb_stripos($row['usages'][$usage]['full_path'] ?? '', $query) !== false));
    }

    public function activeIdsForDomain(string $domain, bool $menuOnly = false): array
    {
        return array_keys(array_filter($this->current($domain), fn ($row) => $row[$menuOnly ? 'menu' : 'active']));
    }

    public function idsForUsages(array $usages, bool $menuOnly = false): array
    {
        return array_keys(array_filter($this->current(Category::DOMAIN_HOSPITAL_MEDICAL), function ($row) use ($usages, $menuOnly) {
            foreach ($usages as $usage) {
                if ($row['usages'][$usage][$menuOnly ? 'visible' : 'enabled'] ?? false) {
                    return true;
                }
            }

            return false;
        }));
    }

    private function current(string $domain): array
    {
        // 다중 항목 검증에서 반복되는 트리 조회만 HTTP 요청 수명 안에서 재사용한다.
        $key = self::class.'.'.$domain;
        $attributes = request()->attributes;
        if (! $attributes->has($key)) {
            $attributes->set($key, $this->resolve(Category::query()->domain($domain)->with('usages')->get()));
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
