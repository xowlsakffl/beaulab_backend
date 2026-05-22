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
            $table->string('phone', 30)->nullable()->comment('사용자 연락처');
            $table->string('signup_channel', 30)->default('EMAIL')->comment('가입 경로(EMAIL, KAKAO, NAVER, APPLE, FACEBOOK, EMAIL_NO_CONTACT, UNKNOWN)');
            $table->timestamp('email_verified_at')->nullable()->comment('이메일 인증 완료 시각');
            $table->string('password')->comment('암호화된 비밀번호');
            $table->string('status')->default('ACTIVE')->comment('계정 상태(ACTIVE, SUSPENDED, BLOCKED, WITHDRAWN)');
            $table->unsignedInteger('warning_count')->default(0)->comment('신고 처리 경고 누적 횟수');
            $table->timestamp('blocked_at')->nullable()->comment('차단 처리 시각');
            $table->timestamp('last_login_at')->nullable()->comment('마지막 로그인 시각');
            $table->timestamp('last_accessed_at')->nullable()->comment('마지막 foreground 접속 기록 시각');
            $table->string('last_access_ip', 45)->nullable()->comment('마지막 foreground 접속 IP');
            $table->timestamps();
            $table->softDeletes()->comment('탈퇴 처리 시각');

            $table->index('status');
            $table->index('signup_channel');
            $table->index('last_login_at');
            $table->index('last_accessed_at');
            $table->index(['status', 'created_at']);
            $table->index(['signup_channel', 'created_at']);
        });

        DB::statement("ALTER TABLE account_users COMMENT = '뷰랩 서비스 일반 사용자 계정 (Sanctum 토큰 인증)'");

        Schema::create('account_user_access_logs', function (Blueprint $table) {
            $table->id()->comment('사용자 접속 기록 ID');
            $table->foreignId('account_user_id')
                ->comment('일반 사용자 account_users ID')
                ->constrained('account_users')
                ->cascadeOnDelete();
            $table->string('ip', 45)->nullable()->comment('접속 IP');
            $table->string('user_agent', 500)->nullable()->comment('접속 User-Agent');
            $table->string('platform', 30)->nullable()->comment('접속 플랫폼(app, web 등)');
            $table->timestamp('accessed_at')->comment('foreground 접속 기록 시각');
            $table->timestamps();

            $table->index(['account_user_id', 'accessed_at']);
            $table->index(['accessed_at', 'account_user_id']);
            $table->index('ip');
        });

        DB::statement("ALTER TABLE account_user_access_logs COMMENT = '일반 사용자 foreground 접속 기록'");

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
        Schema::dropIfExists('account_user_access_logs');
        Schema::dropIfExists('account_users');
        Schema::dropIfExists('password_reset_tokens');
    }
};
