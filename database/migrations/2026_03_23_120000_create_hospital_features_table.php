<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_features', function (Blueprint $table) {
            $table->id()->comment('병원 정보 고유 ID');
            $table->string('code', 100)->unique()->comment('병원 정보 코드');
            $table->string('name', 100)->comment('병원 정보명');
            $table->unsignedInteger('sort_order')->default(0)->comment('정렬 순서');
            $table->string('status', 20)->default('ACTIVE')->comment('병원 정보 상태');
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });

        DB::statement("ALTER TABLE hospital_features COMMENT = '병원 정보 마스터 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_features');
    }
};
