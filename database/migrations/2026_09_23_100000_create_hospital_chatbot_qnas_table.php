<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_chatbot_qnas', function (Blueprint $table) {
            $table->id()->comment('병의원 챗봇 Q&A ID');
            $table->foreignId('parent_id')->nullable()->comment('상위 선택 항목 ID')->constrained('hospital_chatbot_qnas')->restrictOnDelete();
            $table->unsignedTinyInteger('depth')->default(1)->comment('대화 선택 단계 1~3');
            $table->string('category', 100)->comment('Q&A 분류');
            $table->string('question', 255)->comment('병의원 화면에 노출할 질문');
            $table->char('question_key', 64)->comment('상위 ID와 공백·대소문자를 정규화한 선택 문구 해시');
            $table->text('answer')->comment('일반 텍스트 답변');
            $table->string('status', 20)->default('ACTIVE')->comment('노출 상태(ACTIVE, INACTIVE)');
            $table->unsignedInteger('sort_order')->default(0)->comment('같은 상위 내 노출 순서');
            $table->foreignId('created_by_staff_id')
                ->nullable()
                ->comment('등록한 직원 계정 ID')
                ->constrained('account_staffs')
                ->nullOnDelete();
            $table->foreignId('updated_by_staff_id')
                ->nullable()
                ->comment('수정한 직원 계정 ID')
                ->constrained('account_staffs')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 일시');
            $table->char('active_question_key', 64)
                ->nullable()
                ->storedAs('IF(deleted_at IS NULL, question_key, NULL)')
                ->comment('활성 Q&A 질문 중복 방지용 생성 키');

            $table->index(['status', 'sort_order', 'id'], 'hospital_chatbot_qnas_status_sort_idx');
            $table->index(['question_key', 'deleted_at'], 'hospital_chatbot_qnas_question_deleted_idx');
            $table->unique('active_question_key', 'hospital_chatbot_qnas_active_question_unique');
            $table->index(['category', 'status', 'sort_order', 'id'], 'hospital_chatbot_qnas_category_status_sort_idx');
        });

        DB::statement("ALTER TABLE hospital_chatbot_qnas COMMENT = '병의원 관리자 챗봇 공통 Q&A'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_chatbot_qnas');
    }
};
