<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_talks', function (Blueprint $table) {
            $table->id()->comment('병원 토크 게시글 ID');

            $table->foreignId('author_id')
                ->nullable()
                ->comment('작성자 account_users ID')
                ->constrained('account_users')
                ->nullOnDelete();

            $table->string('title', 255)->comment('게시글 제목');
            $table->longText('content')->comment('게시글 내용');

            $table->string('status', 20)->default('ACTIVE')->comment('운영 상태(ACTIVE, INACTIVE)');
            $table->boolean('is_visible')->default(true)->comment('노출 여부');
            $table->string('author_ip', 45)->nullable()->comment('작성자 IP(v4/v6)');

            $table->boolean('is_pinned')->default(false)->comment('상단 고정 여부');
            $table->unsignedInteger('pinned_order')->default(0)->comment('상단 고정 정렬 순서');

            $table->unsignedBigInteger('view_count')->default(0)->comment('조회수');
            $table->unsignedInteger('comment_count')->default(0)->comment('댓글 수');
            $table->unsignedInteger('like_count')->default(0)->comment('좋아요 수');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->index(['status', 'is_visible', 'created_at'], 'hospital_talks_status_visible_created_idx');
            $table->index(['is_pinned', 'pinned_order', 'created_at'], 'hospital_talks_pinned_created_idx');
            $table->index(['author_id', 'created_at'], 'hospital_talks_author_created_idx');
        });

        DB::statement("ALTER TABLE hospital_talks COMMENT = '병원 커뮤니티 토크 게시글'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_talks');
    }
};
