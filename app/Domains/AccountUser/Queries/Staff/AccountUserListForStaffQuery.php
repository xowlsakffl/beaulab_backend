<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Queries\Staff;

use App\Common\Support\DateRangeFilter;
use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * AccountUserListForStaffQuery 역할 정의.
 * 일반 회원 계정 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class AccountUserListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $q = $filters['q'] ?? null;
        $dateType = $filters['date_type'] ?? 'created_at';
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $signupChannel = $filters['signup_channel'] ?? null;
        $status = $filters['status'] ?? null;
        $warningCountMin = $filters['warning_count_min'] ?? null;
        $warningCountMax = $filters['warning_count_max'] ?? null;
        $sort = $filters['sort'] ?? 'id';
        $direction = $filters['direction'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 15;

        $builder = AccountUser::query()->withTrashed()->select([
            'id',
            'name',
            'nickname',
            'email',
            'phone',
            'signup_channel',
            'status',
            'warning_count',
            'email_verified_at',
            'last_login_at',
            'last_accessed_at',
            'last_access_ip',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        if ($q) {
            $builder->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('nickname', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");

                if (ctype_digit((string) $q)) {
                    $w->orWhere('id', (int) $q);
                }
            });
        }

        DateRangeFilter::apply($builder, (string) $dateType, $startDate, $endDate);

        if ($signupChannel) {
            $builder->where('signup_channel', $signupChannel);
        }

        if ($status === AccountUser::STATUS_WITHDRAWN) {
            $builder->where(function ($w) {
                $w->where('status', AccountUser::STATUS_WITHDRAWN)
                    ->orWhereNotNull('deleted_at');
            });
        } elseif ($status) {
            $builder->where('status', $status)->whereNull('deleted_at');
        }

        if ($warningCountMin !== null && $warningCountMin !== '') {
            $builder->where('warning_count', '>=', (int) $warningCountMin);
        }

        if ($warningCountMax !== null && $warningCountMax !== '') {
            $builder->where('warning_count', '<=', (int) $warningCountMax);
        }

        $builder->orderBy($sort, $direction);

        return $builder->paginate($perPage)->withQueryString();
    }
}
