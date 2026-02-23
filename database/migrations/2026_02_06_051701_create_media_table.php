<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id()->comment('미디어(파일) 고유 ID');

            // Polymorphic relation
            $table->string('model_type', 191)->comment('연결 모델 타입(App\\Domains\\Hospital\\Models\\Hospital 등)');
            $table->unsignedBigInteger('model_id')->comment('연결 모델 고유 ID');

            $table->string('collection', 50)->comment('파일 용도(logo, thumbnail, gallery, business_registration_file 등)');

            $table->string('disk', 32)->default('public')->comment('저장 디스크(public, s3 등)');
            $table->string('path', 1024)->comment('파일 저장 경로 또는 키');

            $table->string('mime_type', 127)->nullable()->comment('MIME 타입(image/jpeg, image/png 등)');
            $table->unsignedBigInteger('size')->nullable()->comment('파일 크기(bytes)');

            $table->unsignedInteger('width')->nullable()->comment('이미지 가로(px)');
            $table->unsignedInteger('height')->nullable()->comment('이미지 세로(px)');

            $table->unsignedSmallInteger('sort_order')->default(0)->comment('정렬 순서(내부 이미지용)');
            $table->boolean('is_primary')->default(false)->comment('대표 이미지 여부');

            $table->json('metadata')->nullable()->comment('추가 메타데이터(리사이즈, alt, crop 정보 등)');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            // indexes
            $table->index(['model_type', 'model_id'], 'media_model_index');
            $table->index(['model_type', 'model_id', 'collection'], 'media_model_collection_index');
            $table->index('collection');
            $table->index('disk');
            $table->index('is_primary');
            $table->index('sort_order');
        });

        DB::statement("ALTER TABLE media COMMENT = '미디어(파일) 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
