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
            $table->id()->comment('동영상 ID');

            $table->foreignId('hospital_id')
                ->nullable()
                ->comment('병의원 ID')
                ->constrained('hospitals')
                ->nullOnDelete();

            $table->foreignId('doctor_id')
                ->nullable()
                ->comment('의료진 ID')
                ->constrained('hospital_doctors')
                ->nullOnDelete();

            $table->foreignId('manager_staff_id')
                ->nullable()
                ->comment('담당 직원 ID')
                ->constrained('account_staffs')
                ->nullOnDelete();

            $table->string('title')->comment('동영상 제목');
            $table->text('description')->nullable()->comment('동영상 설명');
            $table->string('external_video_url', 1024)->nullable()->comment('유튜브 영상 URL');
            $table->string('hospital_status', 20)->default('PUBLIC')->comment('병의원 공개 상태');
            $table->string('admin_status', 20)->default('NORMAL')->comment('관리자 강제중지 상태');
            $table->unsignedBigInteger('view_count')->default(0)->comment('조회수');
            $table->unsignedBigInteger('like_count')->default(0)->comment('좋아요수');

            $table->timestamps();
            $table->softDeletes()->comment('삭제 시각');

            $table->index(['deleted_at', 'id'], 'videos_deleted_id_idx');
            $table->index(['deleted_at', 'created_at', 'id'], 'videos_deleted_created_id_idx');
            $table->index(['deleted_at', 'updated_at', 'id'], 'videos_deleted_updated_id_idx');
            $table->index(['deleted_at', 'hospital_status', 'id'], 'videos_deleted_hospital_status_id_idx');
            $table->index(['deleted_at', 'admin_status', 'id'], 'videos_deleted_admin_status_id_idx');
            $table->index(['deleted_at', 'hospital_id', 'created_at', 'id'], 'videos_deleted_hospital_created_idx');
            $table->index(['deleted_at', 'view_count', 'id'], 'videos_deleted_view_count_id_idx');
            $table->index(['deleted_at', 'like_count', 'id'], 'videos_deleted_like_count_id_idx');
            $table->index('manager_staff_id');
        });

        DB::statement("ALTER TABLE hospital_videos COMMENT = '병의원 동영상 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_videos');
    }
};
