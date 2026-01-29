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
        Schema::create('admins', function (Blueprint $table) {
            $table->id()->comment('관리자 고유 ID');

            $table->string('name')->comment('관리자 실명');
            $table->string('nickname')->unique()->comment('관리자 닉네임(표시용)');
            $table->string('email')->unique()->comment('관리자 로그인 이메일');

            $table->timestamp('email_verified_at')->nullable()->comment('이메일 인증 완료 시각');
            $table->string('password')->comment('암호화된 비밀번호');

            $table->text('two_factor_secret')->nullable()->comment('2차 인증 비밀키');
            $table->text('two_factor_recovery_codes')->nullable()->comment('2차 인증 복구 코드');
            $table->timestamp('two_factor_confirmed_at')->nullable()->comment('2차 인증 활성화 시각');

            $table->string('status')->default('active')->comment('계정 상태(active, suspended, blocked)');
            $table->timestamp('last_login_at')->nullable()->comment('마지막 로그인 시각');

            $table->rememberToken()->comment('자동 로그인 토큰');
            $table->timestamps();
            $table->softDeletes()->comment('관리자 비활성/삭제 시각');

            $table->index('status');
            $table->index('last_login_at');
        });

        DB::statement("ALTER TABLE admins COMMENT = '관리자(CMS) 계정 테이블'");

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        DB::statement("ALTER TABLE sessions COMMENT = '관리자(CMS) 계정 세션'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
        Schema::dropIfExists('sessions');
    }
};
