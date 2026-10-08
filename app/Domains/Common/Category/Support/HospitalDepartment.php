<?php

namespace App\Domains\Common\Category\Support;

use App\Domains\Common\Category\Models\Category;

final class HospitalDepartment
{
    public static function labels(bool $activeOnly = false): array
    {
        $attributes = request()->attributes;
        if (! $attributes->has(self::class)) {
            $attributes->set(self::class, Category::query()->domain(Category::DOMAIN_HOSPITAL_DEPARTMENT)
                ->orderBy('sort_order')->orderBy('id')->get(['code', 'name', 'status']));
        }
        $rows = $attributes->get(self::class);

        return ($activeOnly ? $rows->where('status', Category::STATUS_ACTIVE) : $rows)->pluck('name', 'code')->all();
    }
}
