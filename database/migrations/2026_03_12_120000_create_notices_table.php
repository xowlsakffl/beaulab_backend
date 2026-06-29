<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id()->comment('공지사항 ID');

            $table->string('channel', 20)->default('ALL')->comment('공지 채널(ALL, APP_WEB, HOSPITAL, BEAUTY)');
            $table->string('title', 255)->comment('공지 제목');
            $table->longText('content')->comment('에디터 HTML 본문');

            $table->string('status', 20)->default('ACTIVE')->comment('운영 상태(ACTIVE, INACTIVE)');
            $table->boolean('is_pinned')->default(false)->comment('상단 공지 여부');
            // $table->unsignedInteger('pinned_order')->default(0)->comment('상단 공지 정렬 순서');

            $table->boolean('is_publish_period_unlimited')->default(true)->comment('게시기간 무제한 여부');
            $table->timestamp('publish_start_at')->nullable()->comment('게시 시작 일시');
            $table->timestamp('publish_end_at')->nullable()->comment('게시 종료 일시');

            $table->boolean('is_important')->default(false)->comment('관리자 메인 팝업 여부');
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

            $table->index(['deleted_at', 'id'], 'notices_deleted_id_idx');
            $table->index(['deleted_at', 'created_at', 'id'], 'notices_deleted_created_id_idx');
            $table->index(['deleted_at', 'updated_at', 'id'], 'notices_deleted_updated_id_idx');
            $table->index(['deleted_at', 'channel', 'status', 'created_at', 'id'], 'notices_deleted_channel_status_created_idx');
            $table->index(['deleted_at', 'is_pinned', 'publish_start_at', 'id'], 'notices_deleted_pinned_publish_idx');
            $table->index(['deleted_at', 'status', 'publish_start_at', 'publish_end_at', 'id'], 'notices_deleted_status_publish_idx');
            $table->index(['deleted_at', 'is_important', 'status', 'created_at', 'id'], 'notices_deleted_important_status_idx');
        });

        DB::statement("ALTER TABLE notices COMMENT = '관리자 공지사항 관리 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
