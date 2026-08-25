<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_account_identity_verifications', function (Blueprint $table) {
            $table->id()->comment('병의원 계정 본인확인 ID');
            $table->foreignId('hospital_account_invitation_id')->comment('병의원 계정 초대 ID');
            $table->string('provider', 30)->comment('본인확인 제공자');
            $table->char('provider_reference_hash', 64)->comment('제공자 인증 식별값 해시');
            $table->char('token_hash', 64)->unique()->comment('본인확인 증명 토큰 SHA-256 해시');
            $table->string('verified_name', 100)->comment('본인확인 실명');
            $table->string('verified_phone', 30)->comment('본인확인 휴대폰 번호');
            $table->char('ci_hash', 64)->nullable()->comment('연계정보(CI) SHA-256 해시');
            $table->timestamp('verified_at')->comment('본인확인 완료 시각');
            $table->timestamp('expires_at')->comment('본인확인 증명 만료 시각');
            $table->timestamp('consumed_at')->nullable()->comment('본인확인 증명 사용 시각');
            $table->timestamps();

            $table->foreign('hospital_account_invitation_id', 'h_account_identity_invitation_fk')
                ->references('id')
                ->on('hospital_account_invitations')
                ->cascadeOnDelete();
            $table->unique(['provider', 'provider_reference_hash'], 'h_account_identity_provider_ref_unique');
            $table->index(['hospital_account_invitation_id', 'expires_at', 'consumed_at'], 'h_account_identity_active_idx');
        });

        DB::statement("ALTER TABLE hospital_account_identity_verifications COMMENT = '병의원 계정 생성용 휴대폰 본인확인 증명'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_account_identity_verifications');
    }
};
