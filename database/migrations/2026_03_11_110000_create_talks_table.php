<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talks', function (Blueprint $table) {
            $table->id()->comment('토크 게시글 ID');

            $table->foreignId('author_id')
                ->nullable()
                ->comment('작성자 account_users ID')
                ->constrained('account_users')
                ->nullOnDelete();

            $table->string('title', 255)->comment('게시글 제목');
            $table->longText('content')->comment('게시글 내용');

            $table->string('status', 20)->default('ACTIVE')->comment('노출상태(ACTIVE=노출, INACTIVE=미노출)');
            $table->string('post_status', 30)->default('POST_NORMAL')->comment('게시상태(POST_NORMAL, POST_AUTO_BLIND, POST_USER_DELETE, POST_ADMIN_STOP)');
            $table->string('author_ip', 45)->nullable()->comment('작성자 IP(v4/v6)');

            $table->boolean('is_pinned')->default(false)->comment('상단 고정 여부');
            $table->unsignedInteger('pinned_order')->default(0)->comment('상단 고정 정렬 순서');

            $table->unsignedBigInteger('view_count')->default(0)->comment('조회수');
            $table->unsignedInteger('comment_count')->default(0)->comment('댓글수');
            $table->unsignedInteger('like_count')->default(0)->comment('좋아요수');
            $table->unsignedInteger('save_count')->default(0)->comment('저장수');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->index(['post_status', 'status', 'created_at'], 'talks_post_status_status_created_idx');
            $table->index(['is_pinned', 'pinned_order', 'created_at'], 'talks_pinned_created_idx');
            $table->index(['author_id', 'created_at'], 'talks_author_created_idx');
        });

        DB::statement("ALTER TABLE talks COMMENT = '커뮤니티 토크 게시글'");
    }

    public function down(): void
    {
        Schema::dropIfExists('talks');
    }
};
