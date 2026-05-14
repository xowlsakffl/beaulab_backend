<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hospital_review_comments', function (Blueprint $table) {
            $table->id()->comment('병의원후기 댓글 ID');

            $table->foreignId('hospital_review_id')
                ->comment('병의원후기 댓글 게시글 ID')
                ->constrained('hospital_reviews')
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->comment('부모 댓글 ID(대댓글용, 최상위 댓글만 허용)')
                ->constrained('hospital_review_comments')
                ->cascadeOnDelete();

            $table->foreignId('author_id')
                ->nullable()
                ->comment('작성자(사용자) ID')
                ->constrained('account_users')
                ->nullOnDelete();

            $table->longText('content')->comment('댓글 내용');

            $table->string('status', 20)->default('ACTIVE')->comment('노출상태(ACTIVE=노출, INACTIVE=미노출)');
            $table->string('author_ip', 45)->nullable()->comment('작성자 IP(v4/v6)');
            $table->unsignedInteger('like_count')->default(0)->comment('좋아요 수');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->index(['hospital_review_id', 'parent_id'], 'h_review_comments_post_parent_idx');
            $table->index(['status', 'created_at'], 'h_review_comments_status_created_idx');
            $table->index(['author_id', 'created_at'], 'h_review_comments_author_created_idx');
        });

        DB::statement("ALTER TABLE hospital_review_comments COMMENT = '병의원후기 댓글'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospital_review_comments');
    }
};
