<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_account_invitations', function (Blueprint $table) {
            $table->id()->comment('병의원 계정 초대 ID');
            $table->string('source_type', 30)->comment('초대 원본(HOSPITAL, HOSPITAL_ENTRY)');
            $table->foreignId('hospital_id')->nullable()->comment('직접 생성 병의원 ID')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('hospital_entry_id')->nullable()->comment('입점신청 ID')->constrained('hospital_entries')->cascadeOnDelete();
            $table->string('recipient_email')->comment('초대 메일 수신 주소');
            $table->char('token_hash', 64)->unique()->comment('초대 토큰 SHA-256 해시');
            $table->timestamp('expires_at')->comment('초대 만료 시각');
            $table->timestamp('sent_at')->nullable()->comment('초대 발송 시각');
            $table->timestamp('used_at')->nullable()->comment('초대 사용 완료 시각');
            $table->timestamp('revoked_at')->nullable()->comment('초대 폐기 시각');
            $table->foreignId('created_by_staff_id')->nullable()->comment('초대 발송 관리자 ID')->constrained('account_staffs')->nullOnDelete();
            $table->foreignId('completed_account_hospital_id')->nullable()->comment('생성된 병의원 계정 ID');
            $table->timestamps();

            $table->foreign('completed_account_hospital_id', 'h_account_invites_completed_account_fk')
                ->references('id')
                ->on('account_hospitals')
                ->nullOnDelete();
            $table->index(['source_type', 'hospital_id', 'created_at'], 'h_account_invites_hospital_source_idx');
            $table->index(['source_type', 'hospital_entry_id', 'created_at'], 'h_account_invites_entry_source_idx');
            $table->index(['expires_at', 'used_at', 'revoked_at'], 'h_account_invites_active_idx');
        });

        DB::statement("ALTER TABLE hospital_account_invitations COMMENT = '병의원 계정 생성 초대 이력'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_account_invitations');
    }
};
