<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_videos', function (Blueprint $table) {
            $table->id()->comment('동영상 고유 ID');

            $table->foreignId('hospital_id')
                ->nullable()
                ->comment('병원 ID')
                ->constrained('hospitals')
                ->nullOnDelete();

            $table->foreignId('doctor_id')
                ->nullable()
                ->comment('의사 ID')
                ->constrained('hospital_doctors')
                ->nullOnDelete();

            $table->foreignId('submitted_by_account_id')
                ->nullable()
                ->comment('요청 병원 계정 ID')
                ->constrained('account_hospitals')
                ->nullOnDelete();

            $table->string('title')->comment('동영상 제목');
            $table->text('description')->nullable()->comment('동영상 설명');
            $table->boolean('is_usage_consented')->default(false)->comment('영상 사용 동의 여부');

            $table->string('distribution_channel', 20)->default('YOUTUBE')->comment('배포 채널');
            $table->string('external_video_id', 191)->nullable()->comment('외부 채널 영상 ID');
            $table->string('external_video_url', 1024)->nullable()->comment('외부 채널 영상 URL');

            $table->foreignId('thumbnail_media_id')
                ->nullable()
                ->comment('썸네일 미디어 ID')
                ->constrained('media')
                ->nullOnDelete();

            $table->unsignedInteger('duration_seconds')->default(0)->comment('재생 시간(초)');

            $table->string('status', 20)->default('ACTIVE')->comment('동영상 상태');
            $table->timestamp('published_at')->nullable()->comment('게시 시각');
            $table->unsignedBigInteger('view_count')->default(0)->comment('조회수');
            $table->unsignedBigInteger('like_count')->default(0)->comment('좋아요 수');

            $table->timestamp('publish_start_at')->nullable()->comment('게시 시작 시각');
            $table->timestamp('publish_end_at')->nullable()->comment('게시 종료 시각');
            $table->boolean('is_publish_period_unlimited')->default(false)->comment('무기한 게시 여부');

            $table->string('review_status', 20)->default('APPROVED')->comment('검수 상태');
            $table->foreignId('reviewed_by_staff_id')
                ->nullable()
                ->comment('검수 처리 스태프 계정 ID')
                ->constrained('account_staffs')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable()->comment('검수 처리 시각');
            $table->string('reject_reason', 100)->nullable()->comment('반려 사유');
            $table->text('reject_reason_detail')->nullable()->comment('반려 사유 상세');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->index(['status', 'publish_start_at', 'publish_end_at'], 'videos_status_publish_period_idx');
            $table->index(['distribution_channel', 'external_video_id'], 'videos_channel_external_idx');
            $table->index(['hospital_id', 'status'], 'videos_hospital_status_idx');
            $table->index('published_at');
            $table->index('is_publish_period_unlimited');

            $table->index(['review_status', 'created_at'], 'videos_review_created_idx');
            $table->index(['hospital_id', 'review_status'], 'videos_hospital_review_idx');
            $table->index('reviewed_by_staff_id');
            $table->index('submitted_by_account_id');
        });

        DB::statement("ALTER TABLE hospital_videos COMMENT = '병원 동영상 통합 테이블(요청/게시)'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_videos');
    }
};
