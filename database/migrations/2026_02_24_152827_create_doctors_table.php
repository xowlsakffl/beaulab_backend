<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id()->comment('의사 고유 ID');

            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete()->comment('소속 병원 ID');
            $table->unsignedInteger('sort_order')->default(0)->comment('의사 노출 순서');

            $table->string('name')->comment('의사 이름');
            $table->string('gender', 20)->nullable()->comment('의사 성별');
            $table->string('position', 50)->nullable()->comment('의사 직책(대표원장, 원장, 기타)');

            $table->string('profile_image_path', 1024)->nullable()->comment('프로필 이미지 경로');

            $table->date('career_started_at')->nullable()->comment('총 경력 시작일(누적 산정)');

            $table->string('license_number', 100)->nullable()->comment('의사 면허증 번호');
            $table->string('license_image_path', 1024)->nullable()->comment('면허증 이미지 경로');

            $table->boolean('is_specialist')->default(false)->comment('전문의 여부');
            $table->string('specialist_certificate_image_path', 1024)->nullable()->comment('전문의 증명서 이미지 경로');

            $table->json('graduation_certificate_image_paths')->nullable()->comment('졸업증명서 이미지 경로 목록');

            $table->json('educations')->nullable()->comment('학력사항 목록');
            $table->json('careers')->nullable()->comment('경력사항 목록');
            $table->json('etc_contents')->nullable()->comment('기타사항 목록');

            $table->json('etc_certificate_image_paths')->nullable()->comment('기타증명서 이미지 경로 목록');

            $table->string('status', 20)->default('SUSPENDED')->comment('의사 상태(정상, 정지, 비활성)');
            $table->string('allow_status', 20)->default('PENDING')->comment('의사 검수 상태(검수 신청, 검수 완료, 반려 등)');

            $table->timestamps();
            $table->softDeletes()->comment('의사 비활성/삭제 시각');

            $table->index(['hospital_id', 'sort_order']);
            $table->index('status');
            $table->index('allow_status');
            $table->index('is_specialist');
        });

        DB::statement("ALTER TABLE doctors COMMENT = '병원 소속 의사 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
