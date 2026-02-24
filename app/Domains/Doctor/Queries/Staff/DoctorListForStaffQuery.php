<?php

namespace App\Domains\Doctor\Queries\Staff;

use App\Domains\Doctor\Models\Doctor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class DoctorListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $builder = Doctor::query()->select([
            'id', 'hospital_id', 'name', 'position', 'is_specialist', 'sort_order',
            'allow_status', 'status', 'created_at', 'updated_at',
        ]);

        if (! empty($filters['hospital_id'])) {
            $builder->where('hospital_id', (int) $filters['hospital_id']);
        }

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $builder->where(function ($query) use ($q): void {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('position', 'like', "%{$q}%")
                    ->orWhere('license_number', 'like', "%{$q}%");
            });
        }

        if (is_array($filters['status'] ?? null) && $filters['status'] !== []) {
            $builder->whereIn('status', $filters['status']);
        }

        if (is_array($filters['allow_status'] ?? null) && $filters['allow_status'] !== []) {
            $builder->whereIn('allow_status', $filters['allow_status']);
        }

        if (array_key_exists('is_specialist', $filters) && $filters['is_specialist'] !== null) {
            $builder->where('is_specialist', (bool) $filters['is_specialist']);
        }

        $builder->orderBy($filters['sort'] ?? 'id', $filters['direction'] ?? 'desc');

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }
}
