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
        Schema::create('hashtags', function (Blueprint $table) {
            $table->id()->comment('해시태그 고유 ID');
            $table->string('name', 100)->comment('표시용 해시태그명');
            $table->string('normalized_name', 100)->unique()->comment('정규화 해시태그명(중복 방지용)');

            $table->timestamps();

            $table->index('name');
        });

        DB::statement("ALTER TABLE hashtags COMMENT = '공용 해시태그 마스터 테이블(병원/의사/동영상 공통 사용)'");

        Schema::create('hashtaggables', function (Blueprint $table) {
            $table->id()->comment('해시태그 매핑 고유 ID');
            $table->foreignId('hashtag_id')->comment('해시태그 ID')->constrained('hashtags')->cascadeOnDelete();

            $table->string('hashtaggable_type', 191)->comment('매핑 모델 타입(App\\Domains\\Video\\Models\\Video 등)');
            $table->unsignedBigInteger('hashtaggable_id')->comment('매핑 모델 고유 ID');

            $table->timestamps();

            $table->unique(['hashtag_id', 'hashtaggable_type', 'hashtaggable_id'], 'hashtaggables_unique');
            $table->index(['hashtaggable_type', 'hashtaggable_id'], 'hashtaggable_model_index');
        });

        DB::statement("ALTER TABLE hashtaggables COMMENT = '공용 해시태그 다형 매핑 테이블'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hashtaggables');
        Schema::dropIfExists('hashtags');
    }
};
