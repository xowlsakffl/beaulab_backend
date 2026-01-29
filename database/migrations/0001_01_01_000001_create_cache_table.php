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
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary()->comment('캐시 키');
            $table->mediumText('value')->comment('직렬화된 캐시 값');
            $table->integer('expiration')->comment('만료 시간(Unix Timestamp)');
        });

        DB::statement("ALTER TABLE cache COMMENT = '[시스템]Laravel DB 캐시 저장소'");

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary()->comment('락 키');
            $table->string('owner')->comment('락 소유자 식별자');
            $table->integer('expiration')->comment('락 만료 시간(Unix Timestamp)');
        });

        DB::statement("ALTER TABLE cache_locks COMMENT = '[시스템]Laravel 분산 락 관리 테이블'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
