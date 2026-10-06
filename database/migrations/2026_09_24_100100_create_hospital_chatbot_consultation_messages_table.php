<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_chatbot_consultation_messages', function (Blueprint $table) {
            $table->id()->comment('상담 메시지 ID');
            $table->foreignId('consultation_id')->comment('상담 ID');
            $table->unsignedBigInteger('sequence')->comment('상담 내 단조 증가 순번');
            $table->string('sender_type', 20)->comment('발신자 구분(HOSPITAL_USER, AGENT, SYSTEM)');
            $table->unsignedBigInteger('sender_id')->nullable()->comment('발신자 계정 ID. SYSTEM은 비어 있다');
            $table->string('sender_name_snapshot', 191)->nullable()->comment('발신 당시 표시명');
            $table->string('client_message_id', 64)->nullable()->comment('클라이언트 중복 전송 방지 키');
            $table->string('message_type', 20)->default('TEXT')->comment('메시지 종류(TEXT, SYSTEM_TEXT)');
            $table->text('content')->comment('메시지 본문');
            $table->boolean('broadcast_pending')->default(false)->comment('실시간 발행 대기 여부');
            $table->timestamp('created_at')->comment('서버 저장 시각');

            // 인덱스 이름을 직접 지정한다. 테이블명이 길어 자동 생성 이름이 MySQL의 64자 제한을 넘는다.
            $table->foreign('consultation_id', 'hccm_consultation_fk')
                ->references('id')->on('hospital_chatbot_consultations')->cascadeOnDelete();

            $table->unique(['consultation_id', 'sequence'], 'hccm_consultation_sequence_unique');
            // 같은 발신자가 같은 clientMessageId로 재시도해도 한 건만 남는다.
            // client_message_id가 NULL이면 MySQL 유니크 제약이 적용되지 않으므로 SYSTEM 메시지는 자유롭게 쌓인다.
            $table->unique(
                ['consultation_id', 'sender_type', 'sender_id', 'client_message_id'],
                'hccm_consultation_sender_client_unique',
            );
            $table->index(['consultation_id', 'created_at'], 'hccm_consultation_created_at_index');
            $table->index('broadcast_pending', 'hccm_broadcast_pending_index');
        });

        DB::statement("ALTER TABLE hospital_chatbot_consultation_messages COMMENT = '병의원 챗봇 상담 메시지'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_chatbot_consultation_messages');
    }
};
