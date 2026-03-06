<?php

namespace App\Domains\Common\Queries\Category\Staff;

use App\Domains\Common\Models\Category\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class CategoryListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $domain = (string) $filters['domain'];
        $q = $filters['q'] ?? null;
        $status = $filters['status'] ?? null;
        $parentId = $filters['parent_id'] ?? null;
        $isMenuVisible = $filters['is_menu_visible'] ?? null;
        $sort = $filters['sort'] ?? 'sort_order';
        $direction = $filters['direction'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 50;

        $builder = Category::query()
            ->domain($domain)
            ->select([
                'id',
                'domain',
                'parent_id',
                'depth',
                'name',
                'code',
                'full_path',
                'sort_order',
                'status',
                'is_menu_visible',
                'created_at',
                'updated_at',
            ]);

        if ($q) {
            $builder->where(function ($w) use ($q): void {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('full_path', 'like', "%{$q}%");
            });
        }

        if ($parentId !== null) {
            $builder->where('parent_id', (int) $parentId);
        }

        if (is_array($status) && $status !== []) {
            $builder->whereIn('status', $status);
        }

        if ($isMenuVisible !== null) {
            $builder->where('is_menu_visible', (bool) $isMenuVisible);
        }

        $builder->orderBy($sort, $direction);

        if ($sort !== 'id') {
            $builder->orderBy('id');
        }

        return $builder
            ->paginate((int) $perPage)
            ->withQueryString();
    }
}
