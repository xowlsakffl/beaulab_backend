<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talk_comments', function (Blueprint $table) {
            $table->id()->comment('토크 댓글 ID');

            $table->foreignId('talk_id')
                ->comment('토크 게시글 ID')
                ->constrained('talks')
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->comment('부모 댓글 ID(대댓글용, 최상위 댓글만 허용)')
                ->constrained('talk_comments')
                ->cascadeOnDelete();

            $table->foreignId('author_id')
                ->nullable()
                ->comment('작성자(사용자) ID')
                ->constrained('account_users')
                ->nullOnDelete();

            $table->longText('content')->comment('댓글 내용');

            $table->string('status', 20)->default('ACTIVE')->comment('노출상태(ACTIVE=노출, INACTIVE=미노출)');
            $table->string('post_status', 30)->default('POST_NORMAL')->comment('게시상태(POST_NORMAL, POST_AUTO_BLIND, POST_USER_DELETE, POST_ADMIN_STOP)');
            $table->string('author_ip', 45)->nullable()->comment('작성자 IP(v4/v6)');
            $table->unsignedInteger('like_count')->default(0)->comment('좋아요 수');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->index(['talk_id', 'parent_id'], 'talk_comments_post_parent_idx');
            $table->index(['status', 'created_at'], 'talk_comments_status_created_idx');
            $table->index(['post_status', 'status', 'created_at'], 'talk_comments_post_status_status_created_idx');
            $table->index(['author_id', 'created_at'], 'talk_comments_author_created_idx');
        });

        DB::statement("ALTER TABLE talk_comments COMMENT = '토크 댓글'");
    }

    public function down(): void
    {
        Schema::dropIfExists('talk_comments');
    }
};
