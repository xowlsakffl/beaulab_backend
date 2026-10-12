<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_chatbot_events', function (Blueprint $table) {
            $table->comment('병의원 챗봇 이용 이벤트(열람·질문 클릭)');
            $table->id()->comment('이용 이벤트 ID');
            $table->string('event_type', 30)->comment('이벤트 종류(WIDGET_OPENED, QNA_VIEWED)');
            $table->foreignId('hospital_id')->comment('세션의 병의원 ID. 본문 값이 아니라 인증 계정에서 정한다');
            $table->foreignId('account_hospital_id')->nullable()->comment('세션의 병의원 계정 ID. 감사용');
            $table->foreignId('qna_id')->nullable()->comment('QNA_VIEWED일 때 본 Q&A ID. Q&A가 삭제돼도 유지한다');
            $table->string('source_page_path', 255)->nullable()->comment('이벤트 당시 호스트 화면 경로');
            $table->timestamp('occurred_at')->comment('서버 수신 시각. 클라이언트 시계를 믿지 않는다');

            $table->index('occurred_at', 'hce_occurred_idx');
            $table->index(['event_type', 'occurred_at'], 'hce_type_occurred_idx');
            $table->index(['qna_id', 'occurred_at'], 'hce_qna_occurred_idx');
            $table->index(['hospital_id', 'occurred_at'], 'hce_hospital_occurred_idx');

            $table->foreign('hospital_id', 'hce_hospital_fk')->references('id')->on('hospitals')->restrictOnDelete();
            $table->foreign('account_hospital_id', 'hce_account_fk')->references('id')->on('account_hospitals')->nullOnDelete();
            // Q&A는 소프트 삭제라 행이 사라지지 않는다. FK를 걸면 운영 중 하드 삭제를 막아 버리므로 두지 않는다.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_chatbot_events');
    }
};
