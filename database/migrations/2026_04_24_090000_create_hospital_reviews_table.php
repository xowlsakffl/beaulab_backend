<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_reviews', function (Blueprint $table) {
            $table->id()->comment('병의원 후기 ID');

            $table->foreignId('author_id')
                ->nullable()
                ->comment('작성자 account_users ID')
                ->constrained('account_users')
                ->nullOnDelete();

            $table->foreignId('hospital_id')
                ->comment('병의원 ID')
                ->constrained('hospitals')
                ->cascadeOnDelete();

            $table->foreignId('doctor_id')
                ->nullable()
                ->comment('의료진 hospital_doctors ID')
                ->constrained('hospital_doctors')
                ->nullOnDelete();

            $table->string('category_domain', 30)->comment('후기 카테고리 도메인(HOSPITAL_SURGERY, HOSPITAL_TREATMENT)');
            $table->string('title', 255)->comment('후기 제목');
            $table->longText('content')->comment('후기 내용');

            $table->unsignedInteger('cost')->default(0)->comment('시술/수술 비용(만원 단위)');
            $table->unsignedTinyInteger('rating')->comment('평점(1~5)');

            $table->string('status', 20)->default('ACTIVE')->comment('노출상태(ACTIVE, INACTIVE)');
            $table->string('post_status', 30)->default('POST_NORMAL')->comment('게시상태(POST_NORMAL, POST_AUTO_BLIND, POST_USER_DELETE, POST_ADMIN_STOP)');

            $table->boolean('is_main_featured')->default(false)->comment('후기 전체 상단 노출 여부');
            $table->boolean('is_sub_featured')->default(false)->comment('후기 카테고리 상단 노출 여부');

            $table->unsignedBigInteger('view_count')->default(0)->comment('조회수');
            $table->unsignedInteger('comment_count')->default(0)->comment('댓글수');
            $table->unsignedInteger('like_count')->default(0)->comment('좋아요수');
            $table->unsignedInteger('save_count')->default(0)->comment('저장수');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->index(['post_status', 'status', 'created_at'], 'h_reviews_post_status_status_created_idx');
            $table->index(['author_id', 'created_at'], 'h_reviews_author_created_idx');
            $table->index(['hospital_id', 'created_at'], 'h_reviews_hospital_created_idx');
            $table->index(['doctor_id', 'created_at'], 'h_reviews_doctor_created_idx');
            $table->index(['category_domain', 'created_at'], 'h_reviews_domain_created_idx');
            $table->index(['category_domain', 'is_main_featured', 'created_at'], 'h_reviews_domain_main_featured_created_idx');
            $table->index(['category_domain', 'is_sub_featured', 'created_at'], 'h_reviews_domain_sub_featured_created_idx');
            $table->index(['rating', 'created_at'], 'h_reviews_rating_created_idx');
            $table->index(['cost', 'created_at'], 'h_reviews_cost_created_idx');
        });

        DB::statement("ALTER TABLE hospital_reviews COMMENT = '병의원 성형/시술 후기'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_reviews');
    }
};
