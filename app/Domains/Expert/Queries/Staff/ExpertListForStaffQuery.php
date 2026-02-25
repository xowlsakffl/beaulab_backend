<?php

namespace App\Domains\Expert\Queries\Staff;

use App\Domains\Expert\Models\Expert;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ExpertListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $builder = Expert::query()->select([
            'id', 'beauty_id', 'name', 'position', 'sort_order',
            'allow_status', 'status', 'created_at', 'updated_at',
        ]);

        if (! empty($filters['beauty_id'])) {
            $builder->where('beauty_id', (int) $filters['beauty_id']);
        }

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $builder->where(function ($query) use ($q): void {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('position', 'like', "%{$q}%");
            });
        }

        if (is_array($filters['status'] ?? null) && $filters['status'] !== []) {
            $builder->whereIn('status', $filters['status']);
        }

        if (is_array($filters['allow_status'] ?? null) && $filters['allow_status'] !== []) {
            $builder->whereIn('allow_status', $filters['allow_status']);
        }

        $builder->orderBy($filters['sort'] ?? 'id', $filters['direction'] ?? 'desc');

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }
}
