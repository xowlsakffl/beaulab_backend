<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_hospitals', function (Blueprint $table) {
            $table->id()->comment('병원 관리자 고유 ID');
            $table->foreignId('hospital_id')->comment('소속 병원 ID')->constrained('hospitals')->cascadeOnDelete();

            $table->string('name')->comment('연결 병의원명 복제값');
            $table->string('nickname')->unique()->comment('병원 관리자 로그인 아이디');
            $table->string('phone', 50)->comment('인증된 전화번호');
            $table->timestamp('phone_verified_at')->nullable()->comment('전화번호 인증 완료 시각');

            $table->string('password')->comment('암호화된 비밀번호');

            $table->string('status')->default('SUSPENDED')->comment('계정 상태(ACTIVE, SUSPENDED, BLOCKED, WITHDRAWN)');
            $table->timestamp('last_login_at')->nullable()->comment('마지막 로그인 시각');
            $table->timestamps();
            $table->softDeletes()->comment('병원 관리자 비활성/삭제 시각');

            $table->unique('hospital_id');
            $table->index('status');
            $table->index(['last_login_at', 'status', 'hospital_id'], 'account_hospitals_login_status_hospital_idx');
        });

        DB::statement("ALTER TABLE account_hospitals COMMENT = '병원 관리자 계정 테이블'");

        Schema::create('hospital_account_password_resets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_hospital_id')->comment('재설정 대상 계정');
            $table->char('token_hash', 64)->unique('h_account_reset_token_unique');
            $table->char('credential_hash', 64)->comment('발송 시 계정 인증 정보 지문');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('created_by_staff_id')->nullable();
            $table->timestamps();

            $table->foreign('account_hospital_id', 'h_account_reset_account_fk')
                ->references('id')->on('account_hospitals')->cascadeOnDelete();
            $table->foreign('created_by_staff_id', 'h_account_reset_staff_fk')
                ->references('id')->on('account_staffs')->nullOnDelete();
            $table->index(['account_hospital_id', 'created_at'], 'h_account_reset_account_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospital_account_password_resets');
        Schema::dropIfExists('account_hospitals');
    }
};
