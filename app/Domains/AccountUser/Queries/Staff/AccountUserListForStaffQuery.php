<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Queries\Staff;

use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class AccountUserListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $q = $filters['q'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $status = $filters['status'] ?? null;
        $sort = $filters['sort'] ?? 'id';
        $direction = $filters['direction'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 15;

        $builder = AccountUser::query()->select([
            'id',
            'name',
            'email',
            'status',
            'email_verified_at',
            'last_login_at',
            'created_at',
            'updated_at',
        ]);

        if ($q) {
            $builder->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($startDate && $endDate) {
            $builder->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate);
        } elseif ($startDate) {
            $builder->whereDate('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $builder->whereDate('created_at', '<=', $endDate);
        }

        if (is_array($status) && $status !== []) {
            $builder->whereIn('status', $status);
        }

        $builder->orderBy($sort, $direction);

        return $builder->paginate($perPage)->withQueryString();
    }
}
