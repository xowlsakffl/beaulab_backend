<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary()->comment('세션 ID');
            $table->foreignId('user_id')->nullable()->index()->comment('사용자 ID');
            $table->string('ip_address', 45)->nullable()->comment('요청 IP');
            $table->text('user_agent')->nullable()->comment('클라이언트 User-Agent');
            $table->longText('payload')->comment('세션 데이터');
            $table->integer('last_activity')->index()->comment('마지막 활동 시각(Unix timestamp)');
        });

        DB::statement("ALTER TABLE sessions COMMENT = '[시스템] 웹 세션 저장 테이블'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
