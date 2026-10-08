<?php

namespace App\Domains\AccountStaff\Queries\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\AccountStaff\Dto\Staff\StaffManagementDto;
use App\Domains\AccountStaff\Models\AccountStaff;

final class StaffManagementQuery
{
    public function list(array $filters): array
    {
        $query = AccountStaff::query()->with('roles');
        if ($search = trim($filters['search'] ?? '')) {
            $query->where(function ($query) use ($search): void {
                foreach (['nickname', 'name', 'department', 'job_title', 'phone', 'email'] as $field) {
                    $query->orWhere($field, 'like', '%'.addcslashes($search, '%_\\').'%');
                }
                if (ctype_digit($search)) {
                    $query->orWhere('id', $search);
                }
            });
        }
        if ($filters['statuses'] ?? []) {
            $query->whereIn('status', $filters['statuses']);
        }
        if ($filters['roles'] ?? []) {
            $query->whereHas('roles', fn ($q) => $q->where('guard_name', 'staff')->whereIn('name', $filters['roles']));
        }
        $date = $filters['date_field'] ?? 'created_at';
        if ($filters['date_from'] ?? null) {
            $query->where($date, '>=', $filters['date_from'].' 00:00:00');
        }
        if ($filters['date_to'] ?? null) {
            $query->where($date, '<', \Illuminate\Support\Carbon::parse($filters['date_to'])->addDay()->startOfDay());
        }
        $sort = $filters['sort'] ?? 'created_at';
        $query->orderBy($sort, $filters['direction'] ?? 'desc');
        if ($sort !== 'id') {
            $query->orderByDesc('id');
        }

        return PaginatedResponse::fromPaginator($query->paginate($filters['per_page'] ?? 20), fn ($staff) => StaffManagementDto::fromModel($staff));
    }
}
