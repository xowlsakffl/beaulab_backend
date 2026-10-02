<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_chatbot_settings', function (Blueprint $table) {
            $table->id()->comment('병의원 챗봇 공통 설정 ID');
            $table->boolean('is_force_closed')->default(false)->comment('상담원 연결 강제 중단 여부');
            $table->json('public_holiday_codes')->nullable()->comment('매년 휴무로 적용할 공휴일 코드');
            $table->time('break_starts_at')->nullable()->comment('공통 점심 시작 시각');
            $table->time('break_ends_at')->nullable()->comment('공통 점심 종료 시각');
            $table->string('force_closed_reason', 255)->nullable()->comment('강제 중단 사유(내부 확인용)');
            $table->foreignId('updated_by_staff_id')
                ->nullable()
                ->comment('마지막 수정 직원 계정 ID')
                ->constrained('account_staffs')
                ->nullOnDelete();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE hospital_chatbot_settings COMMENT = '병의원 챗봇 공통 설정'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_chatbot_settings');
    }
};
