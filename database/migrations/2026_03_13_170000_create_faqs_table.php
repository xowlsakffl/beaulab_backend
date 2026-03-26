<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id()->comment('자주 묻는 질문 ID');

            $table->string('channel', 20)->default('ALL')->comment('FAQ 채널(ALL, APP_WEB, HOSPITAL, BEAUTY, INTERNAL)');
            $table->string('question', 255)->comment('질문');
            $table->longText('content')->comment('에디터 HTML 답변 본문');

            $table->string('status', 20)->default('ACTIVE')->comment('FAQ 상태(ACTIVE, INACTIVE)');
            //$table->unsignedInteger('sort_order')->default(0)->comment('노출 순서');
            $table->unsignedBigInteger('view_count')->default(0)->comment('조회수');

            $table->foreignId('created_by_staff_id')
                ->nullable()
                ->comment('등록한 관리자 ID')
                ->constrained('account_staffs')
                ->nullOnDelete();

            $table->foreignId('updated_by_staff_id')
                ->nullable()
                ->comment('수정한 관리자 ID')
                ->constrained('account_staffs')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 일시');

            $table->index(['channel', 'status', 'sort_order', 'id'], 'faqs_channel_status_sort_idx');
            $table->index(['status', 'sort_order', 'id'], 'faqs_status_sort_idx');
        });

        DB::statement("ALTER TABLE faqs COMMENT = '관리자 FAQ 관리 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
