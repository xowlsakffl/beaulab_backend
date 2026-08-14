<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Queries\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Domains\HospitalWallet\Models\HospitalWalletTransactionEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class HospitalWalletDashboardForStaffQuery
{
    public function overview(int $year): array
    {
        $now = now();
        $yearStartAt = Carbon::create($year, 1, 1)->startOfDay();
        $yearEndAt = $yearStartAt->copy()->endOfYear();
        $recentStartAt = $now->copy()->startOfMonth()->subMonths(5);
        $recentEndAt = $now->copy()->endOfMonth();
        $yearRows = $this->operationPointsByMonth($yearStartAt, $yearEndAt);
        $recentRows = $recentStartAt->year === $year && $recentEndAt->year === $year
            ? $yearRows
            : $this->operationPointsByMonth($recentStartAt, $recentEndAt);

        return [
            'year' => $year,
            'annual' => $this->annualOperationPoints($yearRows),
            'balances' => $this->currentBalances(),
            'monthly' => $this->recentMonthlyOperationPoints($recentRows, $recentStartAt),
            'category_shares' => $this->eventDBCategoryShares(),
        ];
    }

    public function topHospitals(array $filters): array
    {
        $balanceType = (string) ($filters['balance_type'] ?? HospitalWalletTransactionEntry::BALANCE_TYPE_ALL);
        $builder = DB::table('hospital_wallet_transaction_entries as entries')
            ->join(
                'hospital_wallet_transactions as transactions',
                'transactions.id',
                '=',
                'entries.hospital_wallet_transaction_id',
            )
            ->join(
                'hospital_wallet_operations as operations',
                'operations.id',
                '=',
                'transactions.hospital_wallet_operation_id',
            )
            ->join('hospital_wallets as wallets', 'wallets.id', '=', 'transactions.hospital_wallet_id')
            ->join('hospitals', 'hospitals.id', '=', 'wallets.hospital_id')
            ->where('operations.type', HospitalWalletOperation::TYPE_USAGE)
            ->where('operations.status', HospitalWalletOperation::STATUS_COMPLETED)
            ->where('entries.direction', HospitalWalletTransactionEntry::DIRECTION_DEBIT);

        if ($balanceType !== HospitalWalletTransactionEntry::BALANCE_TYPE_ALL) {
            $builder->where('entries.balance_type', $balanceType);
        }

        if (is_string($filters['start_date'] ?? null)) {
            $builder->where('transactions.created_at', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }

        if (is_string($filters['end_date'] ?? null)) {
            $builder->where('transactions.created_at', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        return $builder
            ->select([
                'hospitals.id as hospital_id',
                'hospitals.name as hospital_name',
            ])
            ->selectRaw('SUM(entries.amount) as used_points')
            ->groupBy('hospitals.id', 'hospitals.name')
            ->orderByDesc('used_points')
            ->orderBy('hospitals.name')
            ->limit(5)
            ->get()
            ->values()
            ->map(static fn ($row, int $index): array => [
                'rank' => $index + 1,
                'hospital_id' => (int) $row->hospital_id,
                'hospital_name' => (string) $row->hospital_name,
                'used_points' => (int) $row->used_points,
            ])
            ->all();
    }

    private function operationPointsByMonth(Carbon $startAt, Carbon $endAt): Collection
    {
        return DB::table('hospital_wallet_operations as operations')
            ->join(
                'hospital_wallet_transactions as transactions',
                'transactions.hospital_wallet_operation_id',
                '=',
                'operations.id',
            )
            ->where('operations.status', HospitalWalletOperation::STATUS_COMPLETED)
            ->whereBetween('transactions.created_at', [$startAt, $endAt])
            ->whereIn('operations.type', [
                HospitalWalletOperation::TYPE_CHARGE,
                HospitalWalletOperation::TYPE_USAGE,
                HospitalWalletOperation::TYPE_REFUND,
                HospitalWalletOperation::TYPE_SERVICE_GRANT,
                HospitalWalletOperation::TYPE_SERVICE_RECLAIM,
            ])
            ->selectRaw("DATE_FORMAT(transactions.created_at, '%Y-%m') as month_key")
            ->selectRaw(
                'SUM(CASE WHEN operations.type = ? THEN operations.amount ELSE 0 END) as service_granted_points',
                [HospitalWalletOperation::TYPE_SERVICE_GRANT],
            )
            ->selectRaw(
                'SUM(CASE WHEN operations.type = ? THEN operations.amount ELSE 0 END) as service_reclaimed_points',
                [HospitalWalletOperation::TYPE_SERVICE_RECLAIM],
            )
            ->selectRaw(
                'SUM(CASE WHEN operations.type = ? THEN operations.amount ELSE 0 END) as charged_points',
                [HospitalWalletOperation::TYPE_CHARGE],
            )
            ->selectRaw(
                'SUM(CASE WHEN operations.type = ? THEN operations.amount ELSE 0 END) as used_points',
                [HospitalWalletOperation::TYPE_USAGE],
            )
            ->selectRaw(
                'SUM(CASE WHEN operations.type = ? THEN operations.amount ELSE 0 END) as refunded_points',
                [HospitalWalletOperation::TYPE_REFUND],
            )
            ->groupByRaw("DATE_FORMAT(transactions.created_at, '%Y-%m')")
            ->get()
            ->keyBy(static fn ($row): string => (string) $row->month_key);
    }

    private function annualOperationPoints(Collection $rows): array
    {
        return [
            'charged_points' => (int) $rows->sum('charged_points'),
            'used_points' => (int) $rows->sum('used_points'),
            'refunded_points' => (int) $rows->sum('refunded_points'),
        ];
    }

    private function recentMonthlyOperationPoints(Collection $rows, Carbon $startAt): array
    {
        return collect(range(0, 5))
            ->map(static function (int $monthOffset) use ($rows, $startAt): array {
                $month = $startAt->copy()->addMonths($monthOffset)->format('Y-m');
                $row = $rows->get($month);

                return [
                    'month' => $month,
                    'service_granted_points' => (int) ($row->service_granted_points ?? 0),
                    'service_reclaimed_points' => (int) ($row->service_reclaimed_points ?? 0),
                    'charged_points' => (int) ($row->charged_points ?? 0),
                    'used_points' => (int) ($row->used_points ?? 0),
                    'refunded_points' => (int) ($row->refunded_points ?? 0),
                ];
            })
            ->all();
    }

    private function currentBalances(): array
    {
        $balances = HospitalWallet::query()
            ->selectRaw('COALESCE(SUM(paid_balance), 0) as paid_points')
            ->selectRaw('COALESCE(SUM(reserved_paid_balance), 0) as reserved_paid_points')
            ->selectRaw('COALESCE(SUM(service_balance), 0) as service_points')
            ->first();
        $paidPoints = max(
            0,
            (int) ($balances?->paid_points ?? 0) - (int) ($balances?->reserved_paid_points ?? 0),
        );
        $servicePoints = (int) ($balances?->service_points ?? 0);

        return [
            'total_points' => $paidPoints + $servicePoints,
            'paid_points' => $paidPoints,
            'service_points' => $servicePoints,
        ];
    }

    private function eventDBCategoryShares(): array
    {
        $eventMorphType = (new HospitalEvent)->getMorphClass();
        $rows = DB::table('hospital_event_dbs as event_dbs')
            ->join('hospital_events as events', 'events.id', '=', 'event_dbs.hospital_event_id')
            ->join('category_assignments as assignments', function ($join) use ($eventMorphType): void {
                $join->on('assignments.categorizable_id', '=', 'events.id')
                    ->where('assignments.categorizable_type', $eventMorphType)
                    ->where('assignments.is_primary', true);
            })
            ->join('categories', 'categories.id', '=', 'assignments.category_id')
            ->join('categories as root_categories', function ($join): void {
                $join->on('root_categories.domain', '=', 'categories.domain')
                    ->on('root_categories.group_code', '=', 'categories.group_code')
                    ->on(
                        'root_categories.full_path',
                        '=',
                        DB::raw("SUBSTRING_INDEX(categories.full_path, ' > ', 1)"),
                    )
                    ->whereNull('root_categories.parent_id');
            })
            ->whereNull('event_dbs.deleted_at')
            ->where('categories.domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->whereIn('categories.group_code', [Category::GROUP_SURGERY, Category::GROUP_TREATMENT])
            ->select([
                'root_categories.id as category_id',
                'root_categories.name as category_name',
                'root_categories.group_code',
            ])
            ->selectRaw('COUNT(DISTINCT event_dbs.id) as application_count')
            ->groupBy('root_categories.id', 'root_categories.name', 'root_categories.group_code')
            ->orderByDesc('application_count')
            ->orderBy('root_categories.name')
            ->get();

        return [
            'surgery' => $this->categoryShareRows($rows->where('group_code', Category::GROUP_SURGERY)->values()),
            'treatment' => $this->categoryShareRows($rows->where('group_code', Category::GROUP_TREATMENT)->values()),
        ];
    }

    private function categoryShareRows(Collection $rows): array
    {
        $total = (int) $rows->sum('application_count');

        return $rows
            ->map(static fn ($row): array => [
                'category_id' => (int) $row->category_id,
                'category_name' => (string) $row->category_name,
                'application_count' => (int) $row->application_count,
                'ratio' => $total > 0 ? round(((int) $row->application_count / $total) * 100, 1) : 0.0,
            ])
            ->all();
    }
}
