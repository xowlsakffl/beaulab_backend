<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id()->comment('동영상 고유 ID');

            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete()->comment('병원 ID(선택)');
            $table->foreignId('beauty_id')->nullable()->constrained('beauties')->nullOnDelete()->comment('뷰티업체 ID(선택)');
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete()->comment('의사 ID(선택, 병원 영상 대표 의사 1명)');
            $table->foreignId('expert_id')->nullable()->constrained('experts')->nullOnDelete()->comment('뷰티전문가 ID(선택, 뷰티 영상 대표 전문가 1명)');

            $table->string('title')->comment('동영상 제목');
            $table->text('description')->nullable()->comment('동영상 설명');

            $table->string('distribution_channel', 20)->default('YOUTUBE')->comment('배포 채널(YOUTUBE 등)');
            $table->string('external_video_id', 191)->nullable()->comment('외부 채널 영상 ID(유튜브 videoId 등)');
            $table->string('external_video_url', 1024)->nullable()->comment('외부 채널 영상 URL');

            $table->foreignId('thumbnail_media_id')->nullable()->constrained('media')->nullOnDelete()->comment('게시 썸네일 미디어 ID');
            $table->unsignedInteger('duration_seconds')->default(0)->comment('게시 재생 시간(초)');

            $table->string('status', 20)->default('ACTIVE')->comment('동영상 상태(ACTIVE, SUSPENDED, PRIVATE)');
            $table->timestamp('published_at')->nullable()->comment('실제 게시 시각');

            $table->unsignedBigInteger('view_count')->default(0)->comment('조회수');
            $table->unsignedBigInteger('like_count')->default(0)->comment('좋아요 수');

            $table->timestamp('publish_start_at')->nullable()->comment('앱 노출 시작 시각');
            $table->timestamp('publish_end_at')->nullable()->comment('앱 노출 종료 시각');
            $table->boolean('is_publish_period_unlimited')->default(false)->comment('무기한 등록 여부');

            $table->timestamps();
            $table->softDeletes()->comment('동영상 비활성/삭제 시각');

            // 조회/노출 패턴 중심 인덱스
            $table->index(['status', 'publish_start_at', 'publish_end_at'], 'videos_status_publish_period_idx');
            $table->index(['distribution_channel', 'external_video_id'], 'videos_channel_external_idx');
            $table->index(['hospital_id', 'status'], 'videos_hospital_status_idx');
            $table->index(['beauty_id', 'status'], 'videos_beauty_status_idx');
            $table->index('published_at');
            $table->index('is_publish_period_unlimited');
        });

        DB::statement("ALTER TABLE videos COMMENT = '검수 승인 후 게시되는 동영상 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
