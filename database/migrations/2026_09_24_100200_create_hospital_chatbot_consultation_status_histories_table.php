<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_chatbot_consultation_status_histories', function (Blueprint $table) {
            $table->id()->comment('상담 상태 이력 ID');
            $table->foreignId('consultation_id')->comment('상담 ID');
            $table->string('from_status', 20)->nullable()->comment('이전 상태. 최초 생성은 비어 있다');
            $table->string('to_status', 20)->comment('변경된 상태');
            $table->string('actor_type', 20)->comment('수행 주체(HOSPITAL_USER, AGENT, SYSTEM)');
            $table->unsignedBigInteger('actor_id')->nullable()->comment('수행자 계정 ID');
            $table->string('reason', 40)->nullable()->comment('변경 사유 코드');
            $table->timestamp('occurred_at')->comment('발생 시각');
            $table->string('request_id', 64)->nullable()->comment('추적용 Request ID');

            // 인덱스 이름을 직접 지정한다. 테이블명이 길어 자동 생성 이름이 MySQL의 64자 제한을 넘는다.
            $table->foreign('consultation_id', 'hccsh_consultation_fk')
                ->references('id')->on('hospital_chatbot_consultations')->cascadeOnDelete();

            $table->index(['consultation_id', 'occurred_at'], 'hccsh_consultation_occurred_at_index');
        });

        DB::statement("ALTER TABLE hospital_chatbot_consultation_status_histories COMMENT = '병의원 챗봇 상담 상태 전이 이력'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_chatbot_consultation_status_histories');
    }
};
