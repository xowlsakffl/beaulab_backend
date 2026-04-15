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
        Schema::create('account_users', function (Blueprint $table) {
            $table->id()->comment('사용자 고유 ID');
            $table->string('name')->comment('사용자 실명');
            $table->string('nickname')->unique()->comment('사용자 닉네임');
            $table->string('email')->unique()->comment('로그인 이메일');
            $table->timestamp('email_verified_at')->nullable()->comment('이메일 인증 완료 시각');
            $table->string('password')->comment('암호화된 비밀번호');
            $table->string('status')->default('ACTIVE')->comment('계정 상태(ACTIVE, SUSPENDED, BLOCKED)');
            $table->timestamp('last_login_at')->nullable()->comment('마지막 로그인 시각');
            $table->timestamps();
            $table->softDeletes()->comment('탈퇴 처리 시각');

            $table->index('status');
            $table->index('last_login_at');
        });

        DB::statement("ALTER TABLE account_users COMMENT = '뷰랩 서비스 일반 사용자 계정 (Sanctum 토큰 인증)'");

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('actor')->comment('user|partner|staff');
            $table->string('email')->comment('비밀번호 재설정 대상 이메일');
            $table->string('token');
            $table->timestamp('created_at')->nullable();

            $table->primary(['actor', 'email']);
            $table->index(['email']);
        });

        DB::statement("ALTER TABLE password_reset_tokens COMMENT = '비밀번호 재설정 토큰 관리'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_users');
        Schema::dropIfExists('password_reset_tokens');
    }
};
