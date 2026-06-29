<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_usages', function (Blueprint $table) {
            $table->id()->comment('카테고리 사용처 고유 ID');

            $table->string('usage', 60)->comment('카테고리 사용처 코드');
            $table->foreignId('category_id')->comment('카테고리 ID')->constrained('categories')->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0)->comment('사용처 내 노출 순서');
            $table->string('status', 20)->default('ACTIVE')->comment('운영 상태(ACTIVE, INACTIVE)');

            $table->timestamps();

            $table->unique(['usage', 'category_id'], 'category_usages_usage_category_unique');
            $table->index(['usage', 'status', 'sort_order', 'id'], 'category_usages_usage_status_sort_id_idx');
            $table->index(['usage', 'status', 'category_id'], 'category_usages_usage_status_category_idx');
            $table->index('category_id');
            $table->index('status');
        });

        DB::statement("ALTER TABLE category_usages COMMENT = '카테고리 사용처별 노출 매핑 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('category_usages');
    }
};
