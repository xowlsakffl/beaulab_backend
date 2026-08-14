<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalWallet\Dto\Staff\HospitalWalletRefundForStaffDto;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Domains\HospitalWallet\Queries\Staff\HospitalWalletRefundForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalWalletRefundProcessForStaffAction
{
    public function __construct(private readonly HospitalWalletRefundForStaffQuery $query) {}

    public function execute(HospitalWalletOperation $operation, array $payload): array
    {
        Gate::authorize('processRefund', HospitalWallet::class);

        $actor = auth()->user();
        if (! $actor instanceof AccountStaff) {
            throw new CustomException(ErrorCode::UNAUTHORIZED);
        }

        $replayed = false;
        $operation = DB::transaction(function () use ($operation, $payload, $actor, &$replayed): HospitalWalletOperation {
            $wallet = $this->query->walletByIdForUpdate((int) $operation->hospital_wallet_id);
            $current = $this->query->operationForUpdate((int) $operation->id);

            if (! $wallet || ! $current || $current->type !== HospitalWalletOperation::TYPE_REFUND) {
                throw new CustomException(ErrorCode::NOT_FOUND, '환불 정보를 찾을 수 없습니다.');
            }

            $targetStatus = (string) $payload['status'];
            $idempotencyKey = (string) $payload['idempotency_key'];
            $metadata = is_array($current->metadata) ? $current->metadata : [];

            if ($current->status !== HospitalWalletOperation::STATUS_PENDING) {
                if ($current->status === $targetStatus
                    && ($metadata['refund_process_idempotency_key'] ?? null) === $idempotencyKey) {
                    $replayed = true;

                    return $this->query->loadDetail($current);
                }

                throw new CustomException(ErrorCode::INVALID_REQUEST, '이미 처리된 환불 건입니다.');
            }

            $refund = $current->refund()->first();
            if (! $refund) {
                throw new CustomException(ErrorCode::NOT_FOUND, '환불 정보를 찾을 수 없습니다.');
            }

            $amount = (int) $current->amount;
            if ((int) $wallet->reserved_paid_balance < $amount) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '예약된 환불 포인트가 올바르지 않습니다.');
            }

            $processedAt = now();
            $attributes = [
                'status' => $targetStatus,
                'processor_type' => $actor->getMorphClass(),
                'processor_id' => (int) $actor->getKey(),
                'processor_kind' => OperationHistory::ACTOR_KIND_STAFF,
                'processed_at' => $processedAt,
                'metadata' => [
                    ...$metadata,
                    'refund_process_idempotency_key' => $idempotencyKey,
                    'refund_process_source' => 'staff.hospital-wallet.refund-process',
                ],
            ];

            if ($targetStatus === HospitalWalletOperation::STATUS_COMPLETED) {
                if ((int) $wallet->paid_balance < $amount) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '보유 유상 포인트가 환불 포인트보다 적습니다.');
                }

                $this->query->createPaidDebitTransaction(
                    $current,
                    $wallet,
                    (int) $wallet->paid_balance - $amount,
                    (int) $wallet->reserved_paid_balance - $amount,
                    $processedAt,
                );
                $this->query->updateWallet(
                    $wallet,
                    (int) $wallet->paid_balance - $amount,
                    (int) $wallet->reserved_paid_balance - $amount,
                    $processedAt,
                );
                $refund->update(['rejection_reason' => null]);
            } else {
                $this->query->updateWallet(
                    $wallet,
                    (int) $wallet->paid_balance,
                    (int) $wallet->reserved_paid_balance - $amount,
                );
                $refund->update(['rejection_reason' => trim((string) $payload['rejection_reason'])]);
            }

            $current->update($attributes);

            return $this->query->loadDetail($current);
        });

        return [
            'refund' => HospitalWalletRefundForStaffDto::fromModel($operation)->toArray(),
            'replayed' => $replayed,
        ];
    }
}
