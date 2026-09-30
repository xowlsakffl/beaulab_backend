<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospital_chatbot_consultations', function (Blueprint $table) {
            $table->unsignedBigInteger('context_qna_id')
                ->nullable()
                ->after('requester_name_snapshot')
                ->comment('상담을 요청하기 직전에 보던 Q&A ID');

            // Q&A는 수정되고 지워진다. 상담원이 보는 것은 "그때 무엇을 읽고 있었는지"이므로
            // 지금의 Q&A가 아니라 요청 당시 본문을 남긴다.
            $table->string('context_qna_question_snapshot', 500)
                ->nullable()
                ->after('context_qna_id')
                ->comment('요청 당시 질문 본문');
            $table->text('context_qna_answer_snapshot')
                ->nullable()
                ->after('context_qna_question_snapshot')
                ->comment('요청 당시 답변 본문');

            $table->foreign('context_qna_id', 'hcc_context_qna_fk')
                ->references('id')->on('hospital_chatbot_qnas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hospital_chatbot_consultations', function (Blueprint $table) {
            $table->dropForeign('hcc_context_qna_fk');
            $table->dropColumn([
                'context_qna_id',
                'context_qna_question_snapshot',
                'context_qna_answer_snapshot',
            ]);
        });
    }
};
