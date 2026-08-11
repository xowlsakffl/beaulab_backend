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
            $table->unsignedBigInteger('paid_balance')->default(0)->comment('유상 충전 잔액(P)');
            $table->unsignedBigInteger('service_balance')->default(0)->comment('서비스 잔액(P)');
            $table->timestamp('last_transaction_at')->nullable()->comment('마지막 거래 일시');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE hospital_wallets COMMENT = '병의원 충전금 지갑'");

        DB::table('hospital_wallets')->insertUsing(
            [
                'hospital_id',
                'paid_balance',
                'service_balance',
                'last_transaction_at',
                'created_at',
                'updated_at',
            ],
            DB::table('hospitals')->selectRaw(
                'id, 0, 0, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP'
            )
        );

        Schema::create('hospital_wallet_transactions', function (Blueprint $table) {
            $table->id()->comment('병의원 충전금 거래 ID');
            $table->foreignId('hospital_wallet_id')
                ->comment('병의원 충전금 지갑 ID')
                ->constrained('hospital_wallets')
                ->restrictOnDelete();

            $table->string('type', 30)->comment('거래 유형');
            $table->unsignedBigInteger('amount')->comment('총 거래 포인트(P)');

            $table->unsignedBigInteger('paid_balance_before')->comment('거래 전 유상 충전 잔액(P)');
            $table->unsignedBigInteger('paid_balance_after')->comment('거래 후 유상 충전 잔액(P)');
            $table->unsignedBigInteger('service_balance_before')->comment('거래 전 서비스 잔액(P)');
            $table->unsignedBigInteger('service_balance_after')->comment('거래 후 서비스 잔액(P)');

            $table->uuid('batch_uuid')->comment('일괄 처리 묶음 UUID');
            $table->string('idempotency_key', 100)->unique()->comment('중복 처리 방지 키');

            $table->string('actor_type', 255)->nullable()->comment('수행자 actor 타입');
            $table->unsignedBigInteger('actor_id')->nullable()->comment('수행자 actor ID');
            $table->string('actor_kind', 40)->comment('수행 주체 구분');

            $table->string('reference_type', 255)->nullable()->comment('연결 대상 타입');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('연결 대상 ID');
            $table->foreignId('reversed_transaction_id')
                ->nullable()
                ->comment('취소 대상 충전금 거래 ID')
                ->constrained('hospital_wallet_transactions')
                ->restrictOnDelete();

            $table->text('reason')->nullable()->comment('처리 사유');
            $table->json('metadata')->nullable()->comment('추가 메타데이터');
            $table->timestamps();

            $table->index(['created_at', 'id'], 'h_wallet_tx_created_id_idx');
            $table->index(['hospital_wallet_id', 'created_at', 'id'], 'h_wallet_tx_wallet_created_idx');
            $table->index(['type', 'created_at', 'id'], 'h_wallet_tx_type_created_idx');
            $table->index(['batch_uuid', 'id'], 'h_wallet_tx_batch_id_idx');
            $table->index(['actor_type', 'actor_id'], 'h_wallet_tx_actor_idx');
            $table->index(['reference_type', 'reference_id'], 'h_wallet_tx_reference_idx');
        });

        DB::statement("ALTER TABLE hospital_wallet_transactions COMMENT = '병의원 충전금 거래 원장'");

        Schema::create('hospital_wallet_transaction_entries', function (Blueprint $table) {
            $table->id()->comment('병의원 충전금 거래 상세 ID');
            $table->unsignedBigInteger('hospital_wallet_transaction_id')->comment('병의원 충전금 거래 ID');
            $table->string('balance_type', 20)->comment('잔액 구분(PAID, SERVICE)');
            $table->string('direction', 10)->comment('증감 구분(CREDIT, DEBIT)');
            $table->unsignedBigInteger('amount')->comment('변동 포인트(P)');
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

        DB::statement("ALTER TABLE hospital_wallet_transaction_entries COMMENT = '병의원 충전금 거래 잔액 상세'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_wallet_transaction_entries');
        Schema::dropIfExists('hospital_wallet_transactions');
        Schema::dropIfExists('hospital_wallets');
    }
};
