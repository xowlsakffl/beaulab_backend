<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_account_phone_verifications', function (Blueprint $table) {
            $table->id()->comment('병의원 계정 휴대폰 인증 ID');
            $table->foreignId('hospital_account_invitation_id')->comment('병의원 계정 초대 ID');
            $table->foreignId('sms_delivery_id')->nullable()->comment('공통 문자 발송 이력 ID');
            $table->string('phone', 30)->comment('인증 대상 휴대폰 번호');
            $table->string('code_hash')->comment('인증번호 단방향 해시');
            $table->char('verification_token_hash', 64)->nullable()->comment('인증 완료 증표 SHA-256 해시');
            $table->unsignedTinyInteger('failed_attempt_count')->default(0)->comment('인증번호 오입력 횟수');
            $table->timestamp('code_expires_at')->comment('인증번호 만료 시각');
            $table->timestamp('verified_at')->nullable()->comment('휴대폰 번호 인증 완료 시각');
            $table->timestamp('verification_expires_at')->nullable()->comment('인증 완료 증표 만료 시각');
            $table->timestamp('consumed_at')->nullable()->comment('인증 완료 증표 사용 시각');
            $table->timestamp('invalidated_at')->nullable()->comment('재발송 또는 오입력 초과로 무효화된 시각');
            $table->timestamps();

            $table->foreign('hospital_account_invitation_id', 'h_account_phone_invitation_fk')
                ->references('id')
                ->on('hospital_account_invitations')
                ->cascadeOnDelete();
            $table->foreign('sms_delivery_id', 'h_account_phone_sms_delivery_fk')
                ->references('id')
                ->on('sms_deliveries')
                ->nullOnDelete();
            $table->index(
                ['hospital_account_invitation_id', 'created_at'],
                'h_account_phone_invitation_created_idx',
            );
            $table->index(
                ['hospital_account_invitation_id', 'phone', 'created_at'],
                'h_account_phone_invitation_phone_idx',
            );
            $table->index(
                ['hospital_account_invitation_id', 'code_expires_at', 'invalidated_at'],
                'h_account_phone_active_code_idx',
            );
            $table->unique('verification_token_hash', 'h_account_phone_token_unique');
        });

        DB::statement("ALTER TABLE hospital_account_phone_verifications COMMENT = '병의원 계정 생성용 휴대폰 문자 인증'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_account_phone_verifications');
    }
};
