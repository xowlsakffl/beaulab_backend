<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Queries\Staff;

use App\Common\Support\DateRangeFilter;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class HospitalWalletOperationListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $builder = HospitalWalletOperation::query()
            ->select('hospital_wallet_operations.*')
            ->join(
                'hospital_wallets',
                'hospital_wallets.id',
                '=',
                'hospital_wallet_operations.hospital_wallet_id',
            )
            ->join('hospitals', 'hospitals.id', '=', 'hospital_wallets.hospital_id')
            ->with([
                'wallet.hospital' => fn ($query) => $query->withTrashed(),
                'transaction.entries',
                'payment',
                'refund.businessRegistrationFile',
                'refund.bankbookFile',
                'requester',
                'processor',
            ]);

        $this->applySearch($builder, $filters['q'] ?? null);
        $this->applyHospital($builder, $filters['hospital_id'] ?? null);
        $this->applyTypeGroup($builder, $filters);
        $this->applyStatus($builder, $filters['statuses'] ?? []);
        DateRangeFilter::apply(
            $builder,
            'hospital_wallet_operations.created_at',
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null,
        );
        $this->applySort(
            $builder,
            $filters['sort'] ?? 'created_at',
            ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc',
        );

        return $builder
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
    }

    public function includesUsage(array $filters): bool
    {
        $typeGroup = $filters['type_group'] ?? HospitalWalletOperation::TYPE_GROUP_CHARGE;

        return $typeGroup === HospitalWalletOperation::TYPE_GROUP_USAGE
            || ($typeGroup === HospitalWalletOperation::TYPE_GROUP_ALL
                && ($this->hasSearch($filters['q'] ?? null) || (int) ($filters['hospital_id'] ?? 0) > 0));
    }

    private function applySearch(Builder $builder, mixed $keyword): void
    {
        if (! $this->hasSearch($keyword)) {
            return;
        }

        $keyword = trim((string) $keyword);
        $staffMorphType = (new AccountStaff)->getMorphClass();

        $builder->where(function (Builder $query) use ($keyword, $staffMorphType): void {
            $query->where('hospitals.name', 'like', "%{$keyword}%")
                ->orWhereExists(function (QueryBuilder $staffQuery) use ($keyword, $staffMorphType): void {
                    $staffQuery
                        ->selectRaw('1')
                        ->from('account_staffs')
                        ->where(function (QueryBuilder $actorQuery) use ($staffMorphType): void {
                            $actorQuery
                                ->where(function (QueryBuilder $requesterQuery) use ($staffMorphType): void {
                                    $requesterQuery
                                        ->whereColumn('account_staffs.id', 'hospital_wallet_operations.requester_id')
                                        ->where('hospital_wallet_operations.requester_type', $staffMorphType);
                                })
                                ->orWhere(function (QueryBuilder $processorQuery) use ($staffMorphType): void {
                                    $processorQuery
                                        ->whereColumn('account_staffs.id', 'hospital_wallet_operations.processor_id')
                                        ->where('hospital_wallet_operations.processor_type', $staffMorphType);
                                });
                        })
                        ->where(function (QueryBuilder $nameQuery) use ($keyword): void {
                            $nameQuery
                                ->where('account_staffs.name', 'like', "%{$keyword}%")
                                ->orWhere('account_staffs.nickname', 'like', "%{$keyword}%")
                                ->orWhere('account_staffs.email', 'like', "%{$keyword}%");
                        });
                });
        });
    }

    private function applyHospital(Builder $builder, mixed $hospitalId): void
    {
        $hospitalId = (int) $hospitalId;
        if ($hospitalId > 0) {
            $builder->where('hospitals.id', $hospitalId);
        }
    }

    private function applyTypeGroup(Builder $builder, array $filters): void
    {
        $typeGroup = is_string($filters['type_group'] ?? null)
            ? $filters['type_group']
            : HospitalWalletOperation::TYPE_GROUP_CHARGE;
        $types = HospitalWalletOperation::typesForGroup($typeGroup);

        if ($types !== []) {
            $builder->whereIn('hospital_wallet_operations.type', $types);

            return;
        }

        if ($typeGroup === HospitalWalletOperation::TYPE_GROUP_ALL && ! $this->includesUsage($filters)) {
            $builder->where('hospital_wallet_operations.type', '!=', HospitalWalletOperation::TYPE_USAGE);
        }
    }

    private function applyStatus(Builder $builder, mixed $statuses): void
    {
        if (! is_array($statuses) || $statuses === []) {
            return;
        }

        $builder->whereIn('hospital_wallet_operations.status', $statuses);
    }

    private function applySort(Builder $builder, mixed $sort, string $direction): void
    {
        match ($sort) {
            'id' => $builder->orderBy('hospital_wallet_operations.id', $direction),
            'amount' => $builder->orderBy('hospital_wallet_operations.amount', $direction),
            default => $builder->orderBy('hospital_wallet_operations.created_at', $direction),
        };

        if ($sort !== 'id') {
            $builder->orderByDesc('hospital_wallet_operations.id');
        }
    }

    private function hasSearch(mixed $keyword): bool
    {
        return is_string($keyword) && trim($keyword) !== '';
    }
}
