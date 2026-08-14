<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Queries;

use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use Illuminate\Support\Facades\DB;

final class HospitalWalletIntegrityAuditQuery
{
    /** @return array<string, int> */
    public function counts(): array
    {
        $pendingRefunds = DB::table('hospital_wallet_operations')
            ->selectRaw('hospital_wallet_id, SUM(amount) AS amount')
            ->where('type', HospitalWalletOperation::TYPE_REFUND)
            ->where('status', HospitalWalletOperation::STATUS_PENDING)
            ->groupBy('hospital_wallet_id');

        $entryTotals = DB::table('hospital_wallet_transaction_entries')
            ->selectRaw('hospital_wallet_transaction_id, SUM(amount) AS amount')
            ->groupBy('hospital_wallet_transaction_id');

        $latestTransactions = DB::table('hospital_wallet_transactions')
            ->selectRaw('hospital_wallet_id, MAX(id) AS transaction_id')
            ->groupBy('hospital_wallet_id');

        return [
            'wallet_reserved_exceeds_paid' => DB::table('hospital_wallets')
                ->whereColumn('reserved_paid_balance', '>', 'paid_balance')
                ->count(),
            'wallet_reserved_pending_refund_mismatch' => DB::table('hospital_wallets as wallets')
                ->leftJoinSub($pendingRefunds, 'pending_refunds', 'pending_refunds.hospital_wallet_id', '=', 'wallets.id')
                ->whereRaw('CAST(wallets.reserved_paid_balance AS SIGNED) <> CAST(COALESCE(pending_refunds.amount, 0) AS SIGNED)')
                ->count(),
            'completed_operation_without_transaction' => DB::table('hospital_wallet_operations as operations')
                ->leftJoin('hospital_wallet_transactions as transactions', 'transactions.hospital_wallet_operation_id', '=', 'operations.id')
                ->where('operations.status', HospitalWalletOperation::STATUS_COMPLETED)
                ->whereNull('transactions.id')
                ->count(),
            'non_completed_operation_with_transaction' => DB::table('hospital_wallet_operations as operations')
                ->join('hospital_wallet_transactions as transactions', 'transactions.hospital_wallet_operation_id', '=', 'operations.id')
                ->where('operations.status', '<>', HospitalWalletOperation::STATUS_COMPLETED)
                ->count(),
            'transaction_wallet_mismatch' => DB::table('hospital_wallet_transactions as transactions')
                ->join('hospital_wallet_operations as operations', 'operations.id', '=', 'transactions.hospital_wallet_operation_id')
                ->whereColumn('transactions.hospital_wallet_id', '<>', 'operations.hospital_wallet_id')
                ->count(),
            'transaction_entry_amount_mismatch' => DB::table('hospital_wallet_transactions as transactions')
                ->leftJoinSub($entryTotals, 'entry_totals', 'entry_totals.hospital_wallet_transaction_id', '=', 'transactions.id')
                ->whereRaw('CAST(transactions.amount AS SIGNED) <> CAST(COALESCE(entry_totals.amount, 0) AS SIGNED)')
                ->count(),
            'transaction_balance_delta_mismatch' => DB::table('hospital_wallet_transactions as transactions')
                ->join('hospital_wallet_operations as operations', 'operations.id', '=', 'transactions.hospital_wallet_operation_id')
                ->whereRaw($this->balanceDeltaMismatchSql())
                ->count(),
            'latest_transaction_wallet_balance_mismatch' => DB::table('hospital_wallets as wallets')
                ->joinSub($latestTransactions, 'latest_transactions', 'latest_transactions.hospital_wallet_id', '=', 'wallets.id')
                ->join('hospital_wallet_transactions as transactions', 'transactions.id', '=', 'latest_transactions.transaction_id')
                ->where(function ($query): void {
                    $query
                        ->whereColumn('wallets.paid_balance', '<>', 'transactions.paid_balance_after')
                        ->orWhereColumn('wallets.service_balance', '<>', 'transactions.service_balance_after');
                })
                ->count(),
            'refund_operation_without_detail' => DB::table('hospital_wallet_operations as operations')
                ->leftJoin('hospital_wallet_refunds as refunds', 'refunds.hospital_wallet_operation_id', '=', 'operations.id')
                ->where('operations.type', HospitalWalletOperation::TYPE_REFUND)
                ->whereNull('refunds.id')
                ->count(),
            'charge_operation_without_payment' => DB::table('hospital_wallet_operations as operations')
                ->leftJoin('hospital_wallet_payments as payments', 'payments.hospital_wallet_operation_id', '=', 'operations.id')
                ->where('operations.type', HospitalWalletOperation::TYPE_CHARGE)
                ->whereNull('payments.id')
                ->count(),
        ];
    }

    private function balanceDeltaMismatchSql(): string
    {
        return <<<'SQL'
            CASE operations.type
                WHEN 'CHARGE' THEN
                    CAST(transactions.paid_balance_after AS SIGNED) - CAST(transactions.paid_balance_before AS SIGNED) <> CAST(transactions.amount AS SIGNED)
                    OR transactions.service_balance_before <> transactions.service_balance_after
                WHEN 'USAGE' THEN
                    CAST(transactions.paid_balance_before AS SIGNED) - CAST(transactions.paid_balance_after AS SIGNED)
                    + CAST(transactions.service_balance_before AS SIGNED) - CAST(transactions.service_balance_after AS SIGNED) <> CAST(transactions.amount AS SIGNED)
                WHEN 'REFUND' THEN
                    CAST(transactions.paid_balance_before AS SIGNED) - CAST(transactions.paid_balance_after AS SIGNED) <> CAST(transactions.amount AS SIGNED)
                    OR transactions.service_balance_before <> transactions.service_balance_after
                WHEN 'SERVICE_GRANT' THEN
                    CAST(transactions.service_balance_after AS SIGNED) - CAST(transactions.service_balance_before AS SIGNED) <> CAST(transactions.amount AS SIGNED)
                    OR transactions.paid_balance_before <> transactions.paid_balance_after
                WHEN 'SERVICE_RECLAIM' THEN
                    CAST(transactions.service_balance_before AS SIGNED) - CAST(transactions.service_balance_after AS SIGNED) <> CAST(transactions.amount AS SIGNED)
                    OR transactions.paid_balance_before <> transactions.paid_balance_after
                ELSE FALSE
            END
            SQL;
    }
}
