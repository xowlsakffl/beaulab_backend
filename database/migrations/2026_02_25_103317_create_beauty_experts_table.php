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
        Schema::create('beauty_experts', function (Blueprint $table) {
            $table->id()->comment('뷰티전문가 고유 ID');

            $table->foreignId('beauty_id')->comment('소속 뷰티 ID')->constrained('beauties')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0)->comment('뷰티전문가 노출 순서');

            $table->string('name')->comment('뷰티전문가 이름');
            $table->string('gender', 20)->nullable()->comment('뷰티전문가 성별');
            $table->string('position', 50)->nullable()->comment('뷰티전문가 직책(대표원장, 원장, 기타)');

            $table->date('career_started_at')->nullable()->comment('총 경력 시작일(누적 산정)');

            $table->json('educations')->nullable()->comment('학력사항 목록');
            $table->json('careers')->nullable()->comment('경력사항 목록');
            $table->json('etc_contents')->nullable()->comment('기타사항 목록');

            // 관련 파일은 media 테이블(collection)로 분리 관리
            // - profile_image
            // - education_certificate_image
            // - etc_certificate_image

            $table->string('status', 20)->default('SUSPENDED')->comment('뷰티전문가 상태(정상, 정지, 비활성)');
            $table->string('allow_status', 20)->default('PENDING')->comment('뷰티전문가 검수 상태(검수 신청, 검수 완료, 반려 등)');

            $table->timestamps();
            $table->softDeletes()->comment('뷰티전문가 비활성/삭제 시각');

            $table->index(['beauty_id', 'sort_order']);
            $table->index('status');
            $table->index('allow_status');
        });

        DB::statement("ALTER TABLE beauty_experts COMMENT = '뷰티 소속 뷰티전문가 테이블'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beauty_experts');
    }
};
