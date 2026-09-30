<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_visit_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary()->comment('비회원 포함 웹 방문 세션 식별자');
            $table->timestamp('started_at')->index()->comment('서버 기준 최초 방문 시각');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visit_sessions');
    }
};
