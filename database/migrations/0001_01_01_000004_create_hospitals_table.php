<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id()->comment('병원 고유 ID');

            $table->string('name')->unique()->comment('병원명');
            $table->string('department', 30)->default('OTHER')->comment('분과(성형외과, 피부과, 의원, 치과, 안과, 한의원, 기타)');

            $table->text('description')->nullable()->comment('병원 소개');
            $table->string('youtube_link', 500)->nullable()->comment('유튜브 링크');

            $table->string('address')->nullable()->comment('병원 주소');
            $table->string('address_detail')->nullable()->comment('병원 상세 주소');

            $table->string('latitude')->nullable()->comment('위도');
            $table->string('longitude')->nullable()->comment('경도');

            $table->string('tel')->nullable()->comment('대표 번호');
            $table->string('ad_reception_phone_1', 50)->nullable()->comment('광고 수신 접수 전화번호 1');
            $table->string('ad_reception_phone_2', 50)->nullable()->comment('광고 수신 접수 전화번호 2');
            $table->string('ad_reception_phone_3', 50)->nullable()->comment('광고 수신 접수 전화번호 3');
            $table->string('email')->nullable()->comment('대표 이메일');

            $table->text('consulting_hours')->nullable()->comment('진료 시간');
            $table->json('operation_hours')->nullable()->comment('요일별 진료 시간');
            $table->text('direction')->nullable()->comment('오시는 길');

            $table->unsignedBigInteger('view_count')->default(0)->comment('조회수');
            $table->unsignedInteger('evaluation_count')->default(0)->comment('노출 병의원 평가 수');
            $table->decimal('evaluation_average_rating', 2, 1)->default(0)->comment('노출 병의원 평가 평균 평점');

            $table->string('allow_status', 20)->default('PENDING')->comment('검수 상태(신청, 검수, 승인, 반려)');
            $table->string('status', 20)->default('SUSPENDED')->comment('운영 상태(정상, 운영중지, 탈퇴)');

            $table->timestamps();
            $table->softDeletes()->comment('병원 비활성/삭제 시각');

            $table->index('allow_status');
            $table->index('status');
            $table->index('department');
            $table->index('view_count');
            $table->index('evaluation_count');
            $table->index('evaluation_average_rating');
            $table->index(['deleted_at', 'id'], 'hospitals_deleted_id_idx');
            $table->index(['deleted_at', 'name', 'id'], 'hospitals_deleted_name_id_idx');
            $table->index(['deleted_at', 'created_at', 'id'], 'hospitals_deleted_created_id_idx');
            $table->index(['deleted_at', 'updated_at', 'id'], 'hospitals_deleted_updated_id_idx');
            $table->index(['deleted_at', 'status', 'id'], 'hospitals_deleted_status_id_idx');
            $table->index(['deleted_at', 'allow_status', 'id'], 'hospitals_deleted_allow_status_id_idx');
            $table->index(['deleted_at', 'department', 'id'], 'hospitals_deleted_department_id_idx');
        });

        DB::statement("ALTER TABLE hospitals COMMENT = '병원 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitals');
    }
};
