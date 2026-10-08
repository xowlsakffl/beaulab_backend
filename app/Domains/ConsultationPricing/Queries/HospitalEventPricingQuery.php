<?php

namespace App\Domains\ConsultationPricing\Queries;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\ConsultationPricing\Support\ConsultationPricingResolver;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Support\Facades\DB;

final class HospitalEventPricingQuery
{
    /** 현재 단가를 SELECT와 WHERE에서 함께 사용해 표시 금액과 금액 검색을 일치시킨다. */
    public function expression(): array
    {
        $at = now();
        $versions = DB::table('consultation_pricing_versions as v')
            ->join('consultation_pricing_scopes as s', 's.id', '=', 'v.scope_id')
            ->whereNull('v.cancelled_at')->where('v.effective_at', '<=', $at)
            ->where('s.scope_key', 'like', 'EVENT:%')
            ->whereNotExists(function ($q) use ($at) {
                $q->selectRaw('1')->from('consultation_pricing_versions as newer')
                    ->whereColumn('newer.scope_id', 'v.scope_id')->whereNull('newer.cancelled_at')->where('newer.effective_at', '<=', $at)
                    ->where(fn ($q) => $q->whereColumn('newer.effective_at', '>', 'v.effective_at')->orWhere(fn ($q) => $q->whereColumn('newer.effective_at', 'v.effective_at')->whereColumn('newer.id', '>', 'v.id')));
            })->get(['s.scope_key', 'v.mode', 'v.ranges'])->keyBy('scope_key');
        $categories = Category::where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)->where('status', Category::STATUS_ACTIVE)->get(['id', 'full_path']);
        $usages = CategoryUsage::whereIn('usage', array_values(ConsultationPricingResolver::USAGES))->where('status', CategoryUsage::STATUS_ACTIVE)->get();
        $groups = [];
        foreach ($usages as $usage) {
            $root = $categories->firstWhere('id', $usage->category_id);
            if (! $root) {
                continue;
            }
            $division = array_search($usage->usage, ConsultationPricingResolver::USAGES, true);
            $version = $versions->get("EVENT:{$division}:{$root->id}");
            if (! $version || $version->mode === 'DEFAULT') {
                $version = $versions->get("EVENT:{$division}:0");
            }
            $ranges = $version ? json_decode($version->ranges, true, flags: JSON_THROW_ON_ERROR) : ConsultationPricingResolver::defaultRanges();
            $groupKey = json_encode($ranges, JSON_THROW_ON_ERROR);
            $groups[$groupKey] ??= ['ids' => [], 'ranges' => $ranges];
            foreach ($categories as $category) {
                if ($category->id === $root->id || str_starts_with((string) $category->full_path, $root->full_path.' > ')) {
                    $groups[$groupKey]['ids'][] = (int) $category->id;
                }
            }
        }
        if ($groups === []) {
            return ['hospital_events.consultation_price', []];
        }
        $sql = 'CASE';
        $bindings = [];
        foreach ($groups as $group) {
            $ids = array_values(array_unique($group['ids']));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql .= " WHEN EXISTS (SELECT 1 FROM category_assignments ca WHERE ca.categorizable_type = ? AND ca.categorizable_id = hospital_events.id AND ca.is_primary = 1 AND ca.category_id IN ({$placeholders})) THEN";
            array_push($bindings, (new HospitalEvent)->getMorphClass(), ...$ids);
            if (count($group['ranges']) === 1) {
                $sql .= ' ?';
                $bindings[] = (int) $group['ranges'][0]['price'];

                continue;
            }
            $sql .= ' CASE';
            foreach ($group['ranges'] as $range) {
                if ($range['upper'] === null) {
                    $sql .= ' ELSE ? END';
                    $bindings[] = (int) $range['price'];
                } else {
                    $sql .= ' WHEN hospital_events.event_price <= ? THEN ?';
                    $bindings[] = (int) $range['upper'];
                    $bindings[] = (int) $range['price'];
                }
            }
        }

        return [$sql.' ELSE hospital_events.consultation_price END', $bindings];
    }
}
