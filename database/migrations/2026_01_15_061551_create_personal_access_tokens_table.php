<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id()->comment('개인 액세스 토큰 ID');

            // 토큰 소유자 (users, 기타 tokenable 모델)
            $table->morphs('tokenable');
            // tokenable_type, tokenable_id 생성

            $table->string('name')
                ->comment('토큰 이름 (용도 식별용)');

            $table->string('token', 64)
                ->unique()
                ->comment('해시된 개인 액세스 토큰');

            $table->text('abilities')
                ->nullable()
                ->comment('토큰 권한 범위(JSON 형식)');

            $table->timestamp('last_used_at')
                ->nullable()
                ->comment('마지막 토큰 사용 시각');

            $table->timestamp('expires_at')
                ->nullable()
                ->comment('토큰 만료 시각');

            $table->timestamps();
        });

        DB::statement("ALTER TABLE personal_access_tokens COMMENT = 'Sanctum 개인 액세스 토큰 관리 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
