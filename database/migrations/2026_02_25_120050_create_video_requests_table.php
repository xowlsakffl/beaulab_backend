<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_requests', function (Blueprint $table) {
            $table->id()->comment('동영상 게시 신청 고유 ID');

            $table->foreignId('hospital_id')->nullable()->comment('병원 ID(선택)')->constrained('hospitals')->nullOnDelete();
            $table->foreignId('beauty_id')->nullable()->comment('뷰티업체 ID(선택)')->constrained('beauties')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->comment('의사 ID(선택)')->constrained('doctors')->nullOnDelete();
            $table->foreignId('expert_id')->nullable()->comment('뷰티전문가 ID(선택)')->constrained('experts')->nullOnDelete();

            $table->foreignId('submitted_by_partner_id')->nullable()->comment('신청 파트너 계정 ID')->constrained('account_partners')->nullOnDelete();

            $table->string('title')->comment('신청 동영상 제목');
            $table->text('description')->nullable()->comment('신청 동영상 설명');
            $table->boolean('is_usage_consented')->default(false)->comment('영상 활용 동의 여부');

            $table->unsignedInteger('duration_seconds')->default(0)->comment('원본 재생 시간(초)');

            $table->timestamp('requested_publish_start_at')->nullable()->comment('요청 게시 시작 시각');
            $table->timestamp('requested_publish_end_at')->nullable()->comment('요청 게시 종료 시각');
            $table->boolean('is_publish_period_unlimited')->default(false)->comment('무기한 등록 요청 여부');

            $table->string('review_status', 20)->default('PENDING')->comment('검수 상태(PENDING, IN_REVIEW, APPROVED, REJECTED)');
            $table->foreignId('reviewed_by_staff_id')->nullable()->comment('검수 처리 스태프 계정 ID')->constrained('account_staffs')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->comment('검수 처리 시각');

            $table->string('reject_reason', 100)->nullable()->comment('반려 사유 코드/요약');
            $table->text('reject_reason_detail')->nullable()->comment('반려 사유 상세 설명');

            $table->timestamps();
            $table->softDeletes()->comment('동영상 게시 신청 비활성/삭제 시각');

            // 조회 패턴 중심 인덱스
            $table->index(['review_status', 'created_at'], 'video_requests_review_created_idx');
            $table->index(['hospital_id', 'review_status'], 'video_requests_hospital_review_idx');
            $table->index(['beauty_id', 'review_status'], 'video_requests_beauty_review_idx');
            $table->index(['requested_publish_start_at', 'requested_publish_end_at'], 'video_requests_requested_period_idx');
            $table->index('is_publish_period_unlimited');
            $table->index('reviewed_by_staff_id');
            $table->index('submitted_by_partner_id');
        });

        DB::statement("ALTER TABLE video_requests COMMENT = '동영상 게시 신청/검수 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('video_requests');
    }
};
