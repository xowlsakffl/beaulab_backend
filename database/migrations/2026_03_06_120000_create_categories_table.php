<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id()->comment('카테고리 고유 ID');

            $table->string('domain', 40)->comment('카테고리 도메인(HOSPITAL_SURGERY, HOSPITAL, HOSPITAL_COMMUNITY, BEAUTY, BEAUTY_COMMUNITY)');
            $table->foreignId('parent_id')->nullable()->comment('상위 카테고리 ID(대분류는 null)')->constrained('categories')->restrictOnDelete();

            $table->unsignedTinyInteger('depth')->comment('카테고리 깊이(1:대분류, 2:중분류, 3:소분류)');
            $table->string('name', 120)->comment('카테고리명');
            $table->string('code', 80)->nullable()->comment('카테고리 운영 코드');
            $table->string('full_path', 255)->nullable()->comment('카테고리 전체 경로(예: 눈 > 쌍꺼풀 > 자연유착)');

            $table->unsignedInteger('sort_order')->default(0)->comment('노출 순서');
            $table->string('status', 20)->default('ACTIVE')->comment('카테고리 상태(ACTIVE, INACTIVE)');
            $table->boolean('is_menu_visible')->default(true)->comment('앱 메뉴 노출 여부');

            $table->timestamps();

            $table->unique(['domain', 'parent_id', 'name'], 'categories_domain_parent_name_unique');
            $table->unique(['domain', 'code'], 'categories_domain_code_unique');

            $table->index('domain');
            $table->index('parent_id');
            $table->index('depth');
            $table->index('status');
            $table->index('is_menu_visible');
            $table->index(['domain', 'depth', 'sort_order'], 'categories_domain_depth_sort_index');
        });

        DB::statement("ALTER TABLE categories COMMENT = '카테고리 마스터 테이블(도메인 통합 + 3단계 계층 구조)'");
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

