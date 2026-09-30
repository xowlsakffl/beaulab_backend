<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_chatbot_operating_hours', function (Blueprint $table) {
            $table->id()->comment('상담 운영시간 ID');
            $table->unsignedTinyInteger('day_of_week')->unique()->comment('ISO 요일(월요일 1~일요일 7)');
            $table->boolean('is_open')->default(false)->comment('상담 운영 여부');
            $table->time('opens_at')->nullable()->comment('상담 시작 시각');
            $table->time('closes_at')->nullable()->comment('상담 종료 시각');
            $table->foreignId('updated_by_staff_id')
                ->nullable()
                ->comment('마지막 수정 직원 계정 ID')
                ->constrained('account_staffs')
                ->nullOnDelete();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE hospital_chatbot_operating_hours COMMENT = '병원 챗봇 요일별 상담 운영시간'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_chatbot_operating_hours');
    }
};
