<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id()->comment('병원 고유 ID');

            $table->string('name')->unique()->comment('병원명');

            $table->text('description')->nullable()->comment('병원 소개');

            $table->string('address')->nullable()->comment('병원 주소');
            $table->string('address_detail')->nullable()->comment('병원 상세 주소');

            $table->string('latitude')->nullable()->comment('위도');
            $table->string('longitude')->nullable()->comment('경도');

            $table->string('tel')->nullable()->comment('대표 번호');
            $table->string('email')->nullable()->comment('대표 이메일');

            $table->text('consulting_hours')->nullable()->comment('진료 시간');
            $table->text('direction')->nullable()->comment('오시는 길');

            $table->unsignedBigInteger('view_count')->default(0)->comment('조회수');

            $table->string('allow_status', 20)->default('PENDING')->comment('병원 검수 상태(검수 신청, 검수 완료, 검수 반려 등)');
            $table->string('status', 20)->default('ACTIVE')->comment('병원 상태(정상, 정지, 탈퇴)');

            $table->timestamps();
            $table->softDeletes()->comment('병원 비활성/삭제 시각');

            $table->index('allow_status');
            $table->index('status');
            $table->index('view_count');
        });

        DB::statement("ALTER TABLE hospitals COMMENT = '병원 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitals');
    }
};
