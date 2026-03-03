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
        Schema::create('account_beauties', function (Blueprint $table) {
            $table->id()->comment('뷰티 관리자 고유 ID');

            $table->foreignId('beauty_id')->comment('소속 뷰티 ID')->constrained('beauties')->cascadeOnDelete();
            $table->string('name')->comment('뷰티 관리자 실명');
            $table->string('nickname')->unique()->comment('뷰티 관리자 로그인 아이디');
            $table->string('email')->unique()->comment('뷰티 관리자 이메일');

            $table->timestamp('email_verified_at')->nullable()->comment('이메일 인증 완료 시각');
            $table->string('password')->comment('암호화된 비밀번호');

            $table->string('status')->default('SUSPENDED')->comment('계정 상태(ACTIVE, SUSPENDED, BLOCKED)');
            $table->timestamp('last_login_at')->nullable()->comment('마지막 로그인 시각');
            $table->timestamps();
            $table->softDeletes()->comment('뷰티 관리자 비활성/삭제 시각');

            $table->index('beauty_id');
            $table->index('status');
            $table->index('last_login_at');
        });

        DB::statement("ALTER TABLE account_beauties COMMENT = '뷰티 관리자 계정 테이블'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_beauties');
    }
};
