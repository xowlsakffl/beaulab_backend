<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_assignments', function (Blueprint $table) {
            $table->id()->comment('카테고리 연결 고유 ID');

            $table->string('categorizable_type', 191)->comment('연결 대상 모델 타입');
            $table->unsignedBigInteger('categorizable_id')->comment('연결 대상 모델 ID');

            $table->foreignId('category_id')->comment('카테고리 ID')->constrained('categories')->restrictOnDelete();

            $table->boolean('is_primary')->default(false)->comment('대표 카테고리 여부');

            $table->timestamps();

            $table->unique(
                ['categorizable_type', 'categorizable_id', 'category_id'],
                'category_assignments_model_category_unique'
            );
            $table->index(['categorizable_type', 'categorizable_id'], 'category_assignments_model_index');
            $table->index('category_id');
            $table->index('is_primary');
        });

        DB::statement("ALTER TABLE category_assignments COMMENT = '카테고리 다형 매핑 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('category_assignments');
    }
};

