<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_wallets', function (Blueprint $table) {
            $table->id()->comment('병의원 충전금 지갑 ID');
            $table->foreignId('hospital_id')
                ->unique()
                ->comment('병의원 ID')
                ->constrained('hospitals')
                ->restrictOnDelete();
            $table->unsignedBigInteger('paid_balance')->default(0)->comment('유상 충전 보유 포인트');
            $table->unsignedBigInteger('reserved_paid_balance')->default(0)->comment('환불 처리 중 예약된 유상 포인트');
            $table->unsignedBigInteger('service_balance')->default(0)->comment('서비스 보유 포인트');
            $table->timestamp('last_transaction_at')->nullable()->comment('마지막 잔액 변경 일시');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE hospital_wallets COMMENT = '병의원 충전금 지갑'");

        DB::statement('ALTER TABLE hospital_wallets ADD CONSTRAINT h_wallet_reserved_lte_paid_chk CHECK (reserved_paid_balance <= paid_balance)');

        DB::table('hospital_wallets')->insertUsing(
            [
                'hospital_id',
                'paid_balance',
                'reserved_paid_balance',
                'service_balance',
                'last_transaction_at',
                'created_at',
                'updated_at',
            ],
            DB::table('hospitals')->selectRaw(
                'id, 0, 0, 0, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP'
            )
        );

        Schema::create('hospital_wallet_operations', function (Blueprint $table) {
            $table->id()->comment('충전금 업무 건 ID');
            $table->foreignId('hospital_wallet_id')
                ->comment('병의원 충전금 지갑 ID')
                ->constrained('hospital_wallets')
                ->restrictOnDelete();
            $table->uuid('batch_uuid')->nullable()->comment('일괄 처리 묶음 UUID');
            $table->string('type', 30)->comment('업무 유형');
            $table->string('status', 30)->comment('업무 처리 상태');
            $table->unsignedBigInteger('amount')->comment('처리 포인트');
            $table->string('idempotency_key', 100)->unique()->comment('중복 처리 방지 키');

            $table->string('requester_type', 255)->nullable()->comment('요청자 모델 타입');
            $table->unsignedBigInteger('requester_id')->nullable()->comment('요청자 ID');
            $table->string('requester_kind', 40)->comment('요청 주체 구분');
            $table->string('processor_type', 255)->nullable()->comment('최종 처리자 모델 타입');
            $table->unsignedBigInteger('processor_id')->nullable()->comment('최종 처리자 ID');
            $table->string('processor_kind', 40)->nullable()->comment('최종 처리 주체 구분');

            $table->string('reference_type', 255)->nullable()->comment('결제 대상 모델 타입');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('결제 대상 ID');
            $table->string('reference_label', 255)->nullable()->comment('결제 대상 표시명 스냅샷');
            $table->text('reason')->nullable()->comment('처리 사유');
            $table->timestamp('processed_at')->nullable()->comment('완료 처리 일시');
            $table->timestamp('canceled_at')->nullable()->comment('취소 일시');
            $table->timestamp('failed_at')->nullable()->comment('실패 일시');
            $table->json('metadata')->nullable()->comment('추가 업무 데이터');
            $table->timestamps();

            $table->index(['created_at', 'id'], 'h_wallet_op_created_id_idx');
            $table->index(['hospital_wallet_id', 'created_at', 'id'], 'h_wallet_op_wallet_created_idx');
            $table->index(['type', 'status', 'created_at', 'id'], 'h_wallet_op_type_status_created_idx');
            $table->index(['batch_uuid', 'id'], 'h_wallet_op_batch_id_idx');
            $table->index(['requester_type', 'requester_id'], 'h_wallet_op_requester_idx');
            $table->index(['processor_type', 'processor_id'], 'h_wallet_op_processor_idx');
            $table->index(['reference_type', 'reference_id'], 'h_wallet_op_reference_idx');
        });

        DB::statement("ALTER TABLE hospital_wallet_operations COMMENT = '병의원 충전금 신청 및 처리 업무 건'");

        DB::statement('ALTER TABLE hospital_wallet_operations ADD CONSTRAINT h_wallet_op_amount_positive_chk CHECK (amount > 0)');
        DB::statement("ALTER TABLE hospital_wallet_operations ADD CONSTRAINT h_wallet_op_type_chk CHECK (type IN ('CHARGE', 'USAGE', 'REFUND', 'SERVICE_GRANT', 'SERVICE_RECLAIM', 'REVERSAL'))");
        DB::statement("ALTER TABLE hospital_wallet_operations ADD CONSTRAINT h_wallet_op_status_chk CHECK (status IN ('PENDING', 'COMPLETED', 'CANCELED', 'REJECTED', 'FAILED'))");

        Schema::create('hospital_wallet_transactions', function (Blueprint $table) {
            $table->id()->comment('병의원 충전금 원장 ID');
            $table->unsignedBigInteger('hospital_wallet_operation_id')->comment('충전금 업무 건 ID');
            $table->foreignId('hospital_wallet_id')
                ->comment('병의원 충전금 지갑 ID')
                ->constrained('hospital_wallets')
                ->restrictOnDelete();
            $table->unsignedBigInteger('amount')->comment('실제 반영 포인트');
            $table->unsignedBigInteger('paid_balance_before')->comment('반영 전 유상 보유 포인트');
            $table->unsignedBigInteger('paid_balance_after')->comment('반영 후 유상 보유 포인트');
            $table->unsignedBigInteger('reserved_paid_balance_before')->comment('반영 전 예약 유상 포인트');
            $table->unsignedBigInteger('reserved_paid_balance_after')->comment('반영 후 예약 유상 포인트');
            $table->unsignedBigInteger('service_balance_before')->comment('반영 전 서비스 포인트');
            $table->unsignedBigInteger('service_balance_after')->comment('반영 후 서비스 포인트');
            $table->foreignId('reversed_transaction_id')
                ->nullable()
                ->comment('취소 대상 원장 ID')
                ->constrained('hospital_wallet_transactions')
                ->restrictOnDelete();
            $table->timestamps();

            $table->foreign('hospital_wallet_operation_id', 'h_wallet_tx_operation_fk')
                ->references('id')
                ->on('hospital_wallet_operations')
                ->restrictOnDelete();
            $table->unique('hospital_wallet_operation_id', 'h_wallet_tx_operation_unique');
            $table->index(['created_at', 'id'], 'h_wallet_tx_created_id_idx');
            $table->index(['hospital_wallet_id', 'created_at', 'id'], 'h_wallet_tx_wallet_created_idx');
        });

        DB::statement("ALTER TABLE hospital_wallet_transactions COMMENT = '실제 잔액 변경이 완료된 불변 원장'");

        DB::statement('ALTER TABLE hospital_wallet_transactions ADD CONSTRAINT h_wallet_tx_amount_positive_chk CHECK (amount > 0)');
        DB::statement('ALTER TABLE hospital_wallet_transactions ADD CONSTRAINT h_wallet_tx_reserved_before_chk CHECK (reserved_paid_balance_before <= paid_balance_before)');
        DB::statement('ALTER TABLE hospital_wallet_transactions ADD CONSTRAINT h_wallet_tx_reserved_after_chk CHECK (reserved_paid_balance_after <= paid_balance_after)');

        Schema::create('hospital_wallet_transaction_entries', function (Blueprint $table) {
            $table->id()->comment('충전금 원장 계정별 증감 ID');
            $table->unsignedBigInteger('hospital_wallet_transaction_id')->comment('병의원 충전금 원장 ID');
            $table->string('balance_type', 20)->comment('잔액 구분(PAID, SERVICE)');
            $table->string('direction', 10)->comment('증감 구분(CREDIT, DEBIT)');
            $table->unsignedBigInteger('amount')->comment('변경 포인트');
            $table->timestamps();

            $table->foreign('hospital_wallet_transaction_id', 'h_wallet_tx_entries_tx_fk')
                ->references('id')
                ->on('hospital_wallet_transactions')
                ->restrictOnDelete();
            $table->unique(
                ['hospital_wallet_transaction_id', 'balance_type'],
                'h_wallet_tx_entries_tx_balance_unique'
            );
        });

        DB::statement("ALTER TABLE hospital_wallet_transaction_entries COMMENT = '충전금 원장 잔액 종류별 증감 상세'");

        DB::statement("ALTER TABLE hospital_wallet_transaction_entries ADD CONSTRAINT h_wallet_entry_balance_type_chk CHECK (balance_type IN ('PAID', 'SERVICE'))");
        DB::statement("ALTER TABLE hospital_wallet_transaction_entries ADD CONSTRAINT h_wallet_entry_direction_chk CHECK (direction IN ('CREDIT', 'DEBIT'))");
        DB::statement('ALTER TABLE hospital_wallet_transaction_entries ADD CONSTRAINT h_wallet_entry_amount_positive_chk CHECK (amount > 0)');

        Schema::create('hospital_wallet_payments', function (Blueprint $table) {
            $table->id()->comment('유상 충전 결제 상세 ID');
            $table->unsignedBigInteger('hospital_wallet_operation_id')->comment('충전 업무 건 ID');
            $table->string('payment_method', 30)->comment('결제 수단');
            $table->string('provider', 50)->nullable()->comment('결제 제공자');
            $table->string('provider_transaction_id', 120)->nullable()->unique()->comment('외부 결제 거래 ID');
            $table->string('depositor_name', 100)->nullable()->comment('입금자명');
            $table->unsignedBigInteger('supply_amount')->comment('공급가액');
            $table->unsignedBigInteger('vat_amount')->comment('부가세');
            $table->unsignedBigInteger('payment_amount')->comment('총 결제금액');
            $table->string('virtual_account_bank', 50)->nullable()->comment('가상계좌 은행');
            $table->string('virtual_account_number', 100)->nullable()->comment('가상계좌 번호');
            $table->timestamp('virtual_account_expires_at')->nullable()->comment('가상계좌 입금 기한');
            $table->timestamp('paid_at')->nullable()->comment('입금 완료 일시');
            $table->json('metadata')->nullable()->comment('결제사 응답 스냅샷');
            $table->timestamps();

            $table->foreign('hospital_wallet_operation_id', 'h_wallet_payment_operation_fk')
                ->references('id')
                ->on('hospital_wallet_operations')
                ->restrictOnDelete();
            $table->unique('hospital_wallet_operation_id', 'h_wallet_payment_operation_unique');
            $table->index(['paid_at', 'id'], 'h_wallet_payment_paid_id_idx');
        });

        DB::statement("ALTER TABLE hospital_wallet_payments COMMENT = '유상 충전 결제 및 가상계좌 상세'");

        DB::statement('ALTER TABLE hospital_wallet_payments ADD CONSTRAINT h_wallet_payment_amount_chk CHECK (payment_amount = supply_amount + vat_amount)');

        Schema::create('hospital_wallet_refunds', function (Blueprint $table) {
            $table->id()->comment('충전금 환불 상세 ID');
            $table->unsignedBigInteger('hospital_wallet_operation_id')->comment('환불 업무 건 ID');
            $table->unsignedBigInteger('supply_amount')->comment('환불 공급가액');
            $table->unsignedBigInteger('vat_amount')->comment('환불 부가세');
            $table->unsignedBigInteger('refund_amount')->comment('최종 환불 금액');
            $table->string('bank_name', 50)->comment('환불 은행');
            $table->text('account_number')->comment('암호화된 환불 계좌번호');
            $table->text('rejection_reason')->nullable()->comment('환불 반려 사유');
            $table->timestamps();

            $table->foreign('hospital_wallet_operation_id', 'h_wallet_refund_operation_fk')
                ->references('id')
                ->on('hospital_wallet_operations')
                ->restrictOnDelete();
            $table->unique('hospital_wallet_operation_id', 'h_wallet_refund_operation_unique');
        });

        DB::statement("ALTER TABLE hospital_wallet_refunds COMMENT = '충전금 환불 계좌 및 금액 상세'");
        DB::statement('ALTER TABLE hospital_wallet_refunds ADD CONSTRAINT h_wallet_refund_amount_chk CHECK (refund_amount = supply_amount + vat_amount)');
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_wallet_refunds');
        Schema::dropIfExists('hospital_wallet_payments');
        Schema::dropIfExists('hospital_wallet_transaction_entries');
        Schema::dropIfExists('hospital_wallet_transactions');
        Schema::dropIfExists('hospital_wallet_operations');
        Schema::dropIfExists('hospital_wallets');
    }
};
