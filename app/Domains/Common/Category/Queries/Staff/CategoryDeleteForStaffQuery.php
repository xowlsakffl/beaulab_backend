<?php

namespace App\Domains\Common\Category\Queries\Staff;

use App\Domains\Common\Category\Models\Category;
use Illuminate\Support\Facades\DB;

/**
 * CategoryDeleteForStaffQuery 역할 정의.
 * 공통 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class CategoryDeleteForStaffQuery
{
    public function hasChildren(Category $category): bool
    {
        return Category::query()
            ->where('domain', $category->domain)
            ->where('parent_id', $category->id)
            ->exists();
    }

    public function hasAssignments(Category $category): bool
    {
        foreach (['event', 'review', 'video'] as $prefix) {
            if (DB::table($prefix.'_exposure_positions')->whereIn('key', [
                'SURGERY:'.$category->id, 'PETIT:'.$category->id,
                'SURGERY_CATEGORY_BANNER:'.$category->id, 'PETIT_CATEGORY_BANNER:'.$category->id,
            ])->exists()) {
                return true;
            }
        }
        if ($category->domain === Category::DOMAIN_HOSPITAL_DEPARTMENT && DB::table('hospitals')->where('department', $category->code)->exists()) {
            return true;
        }

        return DB::table('category_assignments')
            ->where('category_id', $category->id)
            ->exists()
            || DB::table('remote_consultation_categories')->where('category_id', $category->id)->exists()
            || DB::table('banner_exposures')->where('category_id', $category->id)->exists()
            || DB::table('consultation_pricing_scopes')->whereIn('scope_key', array_map(
                fn ($group) => "EVENT:{$group}:{$category->id}", Category::groupCodes()
            ))->exists();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}
