<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_talk_comments', function (Blueprint $table) {
            $table->id()->comment('병원 토크 댓글 ID');

            $table->foreignId('hospital_talk_id')
                ->comment('병원 토크 게시글 ID')
                ->constrained('hospital_talks')
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->comment('부모 댓글 ID(대댓글용)')
                ->constrained('hospital_talk_comments')
                ->cascadeOnDelete();

            $table->foreignId('author_id')
                ->nullable()
                ->comment('작성자(사용자) ID')
                ->constrained('account_users')
                ->nullOnDelete();

            $table->longText('content')->comment('댓글 내용');

            $table->string('status', 20)->default('ACTIVE')->comment('운영 상태(ACTIVE, INACTIVE)');
            $table->boolean('is_visible')->default(true)->comment('노출 여부');
            $table->string('author_ip', 45)->nullable()->comment('작성자 IP(v4/v6)');
            $table->unsignedInteger('like_count')->default(0)->comment('좋아요 수');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->index(['hospital_talk_id', 'parent_id'], 'hospital_talk_comments_post_parent_idx');
            $table->index(['status', 'is_visible', 'created_at'], 'hospital_talk_comments_status_visible_created_idx');
            $table->index(['author_id', 'created_at'], 'hospital_talk_comments_author_created_idx');
        });

        DB::statement("ALTER TABLE hospital_talk_comments COMMENT = '병원 토크 댓글'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_talk_comments');
    }
};
